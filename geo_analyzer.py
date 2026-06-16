#!/usr/bin/env python3
"""
GEO 实时分析引擎
接收关键词 → 全平台查询 → 多维分析 → JSON输出

使用方式:
  python3 geo_analyzer.py "西安科技大学高新学院"
  python3 geo_analyzer.py --json "关键词"
"""

import os, sys, re, json, time, random
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Optional
import concurrent.futures

BASE_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(BASE_DIR))

import yaml
import requests

# ---- 加载配置 ----
CONFIG_PATH = BASE_DIR / 'config.yaml'

def load_config() -> dict:
    with open(CONFIG_PATH, 'r') as f:
        raw = f.read()
    def expand_env(m):
        var = m.group(1) or m.group(2)
        return os.environ.get(var, '')
    raw = re.sub(r'\$\{(\w+)\}', expand_env, raw)
    raw = re.sub(r'\$(\w+)', expand_env, raw)
    return yaml.safe_load(raw)

CFG = load_config()
COMPANY_FULL = CFG['company']['full_name']
COMPANY_VARIANTS = CFG['company']['variants']

# ============================= 品牌检测 =============================
ALL_BRANDS = [COMPANY_FULL] + COMPANY_VARIANTS
# 通用品牌词库（常见品牌/公司名，用于竞品检测）
COMMON_BRANDS = [
    '西安科技大学', '西安科大', '西安电子科技大学', '西北大学', '长安大学',
    '西安交通大学', '陕西师范大学', '西北工业大学', '西安理工大学',
    '西安建筑科技大学', '西安石油大学', '西安工程大学', '西安工业大学',
    '西京学院', '西安培华学院', '西安外事学院', '西安翻译学院',
    '百度', '阿里巴巴', '腾讯', '字节跳动', '华为', '小米',
    '知乎', '百度百科', '维基百科', 'CSDN', '博客园', '简书',
]

def detect_brands(text: str) -> List[Dict]:
    """从回复中检测所有品牌/公司名提及"""
    hits = []
    seen = set()
    for brand in ALL_BRANDS + COMMON_BRANDS:
        if brand in text and brand not in seen:
            count = text.count(brand)
            # 计算首次出现位置（越靠前越重要）
            pos = text.index(brand)
            hits.append({
                'name': brand,
                'count': count,
                'position': pos,
                'is_primary': brand in ALL_BRANDS,
            })
            seen.add(brand)
    hits.sort(key=lambda x: x['position'])
    return hits

# ============================= 来源分析 =============================
SOURCE_PATTERNS = {
    '百科类': ['百度百科', '维基百科', 'wikipedia', 'baike.baidu.com', 'wiki', 'MBA智库百科', '互动百科'],
    '问答社区': ['知乎', 'zhihu.com', '知道', 'zhidao.baidu.com', '悟空问答', 'Stack Overflow'],
    '新闻媒体': ['人民网', '新华网', '新浪', '搜狐', '网易', '腾讯新闻', '澎湃新闻', '36氪', '虎嗅'],
    '官方/学术': ['教育部', '官网', '.edu.cn', '中国教育在线', '学信网', '政府', '.gov.cn'],
    '技术社区': ['CSDN', '博客园', 'cnblogs', '简书', 'jianshu', '掘金', 'juejin', 'GitHub', 'github.com'],
    '社交媒体': ['微博', 'weibo', '微信公众号', '小红书', '抖音', 'B站', 'bilibili'],
    '视频平台': ['B站', 'bilibili', '抖音', '快手', 'YouTube', '腾讯视频', '爱奇艺'],
}

def analyze_sources(text: str) -> Dict:
    """分析AI回复引用的内容来源"""
    results = {}
    for category, patterns in SOURCE_PATTERNS.items():
        found = []
        for p in patterns:
            if p.lower() in text.lower():
                found.append(p)
        if found:
            results[category] = found
    return results

# ============================= 内容类型分析 =============================
CONTENT_TYPES = {
    '介绍/概述': ['是什么', '介绍', '概述', '简介', '定义', '是指', '称为'],
    '对比/评测': ['对比', '比较', '区别', '优劣', '哪个好', '测评', '评测', 'vs'],
    '教程/指南': ['如何', '怎么', '教程', '指南', '步骤', '方法', '技巧', '攻略'],
    '排名/榜单': ['排名', '榜单', 'top', '十大', '最好', '推荐', '排行'],
    '新闻/动态': ['最新', '近日', '发布', '宣布', '上线', '更新'],
    '数据/统计': ['数据', '统计', '占比', '比例', '人数', '分数线', '学费'],
}

def analyze_content_type(text: str) -> List[str]:
    """分析AI回复的内容类型"""
    types = []
    for ctype, patterns in CONTENT_TYPES.items():
        for p in patterns:
            if p in text:
                types.append(ctype)
                break
    return types if types else ['综合信息']

# ============================= URL/引用检测 =============================
def extract_urls(text: str) -> List[str]:
    """提取回复中的URL"""
    urls = re.findall(r'https?://[^\s<>"{}|\\^`\[\]]+', text)
    return urls[:10]

# ============================= API 查询 =============================
def _api_call(base_url: str, api_key: str, model: str, prompt: str) -> str:
    headers = {
        'Authorization': f'Bearer {api_key}',
        'Content-Type': 'application/json',
    }
    payload = {
        'model': model,
        'messages': [{'role': 'user', 'content': prompt}],
        'temperature': CFG['crawl']['temperature'],
        'max_tokens': CFG['crawl']['max_tokens'],
    }
    resp = requests.post(base_url, json=payload, headers=headers, timeout=90)
    resp.raise_for_status()
    return resp.json()['choices'][0]['message']['content']

def _api_ready(platform_pkey: str) -> bool:
    api_cfg = CFG['apis'].get(platform_pkey, {})
    if not api_cfg.get('enabled', False):
        return False
    key = api_cfg.get('api_key', '')
    if not key or key.startswith('$'):
        return False
    if platform_pkey == 'wenxin':
        sk = api_cfg.get('secret_key', '')
        if not sk or sk.startswith('$'):
            return False
    return True

def query_platform(platform_pkey: str, keyword: str) -> dict:
    """查询单个平台并返回分析结果"""
    api_cfg = CFG['apis'][platform_pkey]
    if not _api_ready(platform_pkey):
        return {
            'platform': api_cfg.get('name', platform_pkey),
            'pkey': platform_pkey,
            'success': False,
            'error': 'API 未配置',
            'response': '',
            'brands': [],
            'sources': {},
            'content_types': [],
            'urls': [],
            'response_length': 0,
        }

    prompt = CFG['crawl']['prompt_template'].format(keyword=keyword)
    name = api_cfg.get('name', platform_pkey)

    for attempt in range(CFG['crawl']['retry_max']):
        try:
            time.sleep(random.uniform(0.2, 0.8))

            if platform_pkey == 'wenxin':
                # 文心一言特殊处理
                import time as t
                token_cache = BASE_DIR / 'data' / '.wenxin_token.json'
                if token_cache.exists():
                    try:
                        cache = json.loads(token_cache.read_text())
                        if cache.get('expires_at', 0) > t.time() + 86400:
                            access_token = cache['access_token']
                        else:
                            raise ValueError('token expired')
                    except:
                        params = {
                            'grant_type': 'client_credentials',
                            'client_id': api_cfg['api_key'],
                            'client_secret': api_cfg['secret_key'],
                        }
                        resp = requests.post('https://aip.baidubce.com/oauth/2.0/token', params=params, timeout=30)
                        resp.raise_for_status()
                        data = resp.json()
                        access_token = data['access_token']
                        token_cache.write_text(json.dumps({
                            'access_token': access_token,
                            'expires_at': t.time() + data.get('expires_in', 2592000),
                        }))
                else:
                    params = {
                        'grant_type': 'client_credentials',
                        'client_id': api_cfg['api_key'],
                        'client_secret': api_cfg['secret_key'],
                    }
                    resp = requests.post('https://aip.baidubce.com/oauth/2.0/token', params=params, timeout=30)
                    resp.raise_for_status()
                    data = resp.json()
                    access_token = data['access_token']
                    token_cache.write_text(json.dumps({
                        'access_token': access_token,
                        'expires_at': t.time() + data.get('expires_in', 2592000),
                    }))

                url = f'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro?access_token={access_token}'
                payload = {'messages': [{'role': 'user', 'content': prompt}]}
                resp = requests.post(url, json=payload, timeout=90)
                resp.raise_for_status()
                response = resp.json()['result']
            elif platform_pkey == 'doubao':
                response = _api_call(api_cfg['base_url'], api_cfg['api_key'], api_cfg['model'], prompt)
            elif platform_pkey == 'hunyuan':
                response = _api_call(api_cfg['base_url'], api_cfg['api_key'], api_cfg['model'], prompt)
            elif platform_pkey == 'qianwen':
                response = _api_call(api_cfg['base_url'], api_cfg['api_key'], api_cfg['model'], prompt)
                response = _api_call(api_cfg['base_url'], api_cfg['api_key'], api_cfg['model'], prompt)
            else:
                response = _api_call(api_cfg['base_url'], api_cfg['api_key'], api_cfg['model'], prompt)

            return {
                'platform': name,
                'pkey': platform_pkey,
                'success': True,
                'error': '',
                'response': response,
                'response_length': len(response),
                'brands': detect_brands(response),
                'sources': analyze_sources(response),
                'content_types': analyze_content_type(response),
                'urls': extract_urls(response),
            }
        except Exception as e:
            err = str(e)[:200]
            if '429' in err and attempt < CFG['crawl']['retry_max'] - 1:
                time.sleep(2 ** attempt + random.uniform(0, 1))
                continue
            return {
                'platform': name,
                'pkey': platform_pkey,
                'success': False,
                'error': err,
                'response': '',
                'brands': [],
                'sources': {},
                'content_types': [],
                'urls': [],
                'response_length': 0,
            }

# ============================= 难度评分 =============================
def calculate_difficulty(results: List[dict]) -> Dict:
    """根据各平台分析结果计算收录难度"""
    total_platforms = len(results)
    success_count = sum(1 for r in results if r['success'])
    if success_count == 0:
        return {'score': 'unknown', 'level': 0, 'label': '无法评估', 'reasons': ['所有平台API未配置']}

    # 品牌提及密度
    all_brands = {}
    for r in results:
        for b in r.get('brands', []):
            name = b['name']
            if name not in all_brands:
                all_brands[name] = {'count': 0, 'platforms': 0}
            all_brands[name]['count'] += b['count']
            all_brands[name]['platforms'] += 1

    primary_brands = [b for b in all_brands if b in ALL_BRANDS]
    competitor_brands = [b for b in all_brands if b not in ALL_BRANDS]

    # 品牌提及平台数
    primary_platforms = max([all_brands[b]['platforms'] for b in primary_brands]) if primary_brands else 0
    competitor_count = len(competitor_brands)

    # 来源丰富度
    all_sources = set()
    for r in results:
        for cat in r.get('sources', {}):
            all_sources.add(cat)
    source_richness = len(all_sources)

    # 评分逻辑（0-100，越高越难）
    difficulty_score = 0
    reasons = []

    # 主要品牌覆盖度高 → 难度高
    primary_coverage = primary_platforms / max(success_count, 1)
    if primary_coverage > 0.7:
        difficulty_score += 30
        reasons.append(f'主要品牌已覆盖 {primary_platforms}/{success_count} 个平台')
    elif primary_coverage > 0.3:
        difficulty_score += 15
        reasons.append(f'主要品牌部分覆盖 {primary_platforms}/{success_count} 个平台')
    else:
        reasons.append('主要品牌覆盖度低，有较大优化空间')

    # 竞品多 → 难度高
    if competitor_count > 8:
        difficulty_score += 30
        reasons.append(f'竞品众多（{competitor_count}+ 个品牌）')
    elif competitor_count > 4:
        difficulty_score += 15
        reasons.append(f'竞品适中（{competitor_count} 个品牌）')
    elif competitor_count > 0:
        difficulty_score += 5
        reasons.append(f'少量竞品（{competitor_count} 个品牌），机会较大')

    # 来源丰富 → 需要多类型内容
    if source_richness >= 4:
        difficulty_score += 20
        reasons.append(f'AI引用来源丰富（{source_richness} 类），需多元化内容策略')
    elif source_richness >= 2:
        difficulty_score += 10
        reasons.append(f'AI引用来源适中（{source_richness} 类）')

    level = 1 if difficulty_score < 30 else (2 if difficulty_score < 60 else 3)
    labels = {1: '容易', 2: '中等', 3: '困难'}
    colors = {1: '#10b981', 2: '#f59e0b', 3: '#ef4444'}

    return {
        'score': difficulty_score,
        'level': level,
        'label': labels[level],
        'color': colors[level],
        'reasons': reasons,
        'primary_coverage': round(primary_coverage * 100),
        'competitor_count': competitor_count,
        'source_richness': source_richness,
    }

# ============================= 平台推荐 =============================
def generate_recommendations(results: List[dict], difficulty: Dict) -> List[Dict]:
    """生成发帖平台推荐"""
    recommendations = []

    # 按品牌提及数排序平台
    platform_brand_counts = []
    for r in results:
        if not r['success']:
            continue
        primary_count = sum(1 for b in r.get('brands', []) if b['is_primary'])
        competitor_count = sum(1 for b in r.get('brands', []) if not b['is_primary'])
        platform_brand_counts.append({
            'platform': r['platform'],
            'pkey': r['pkey'],
            'primary': primary_count,
            'competitor': competitor_count,
        })

    platform_brand_counts.sort(key=lambda x: x['primary'])

    # 推荐：品牌提及少的平台
    for p in platform_brand_counts[:4]:
        if p['primary'] == 0:
            recommendations.append({
                'platform': p['platform'],
                'pkey': p['pkey'],
                'priority': 'high',
                'reason': f'{p["platform"]} 当前未提及您的品牌，优先布局',
                'action': '立即发布品牌介绍、产品文章',
            })
        elif p['primary'] <= 2:
            recommendations.append({
                'platform': p['platform'],
                'pkey': p['pkey'],
                'priority': 'medium',
                'reason': f'{p["platform"]} 品牌提及较少，加强内容覆盖',
                'action': '定期发布深度内容、行业分析',
            })

    # 内容类型建议
    all_content_types = set()
    for r in results:
        for ct in r.get('content_types', []):
            all_content_types.add(ct)

    content_recs = []
    if '介绍/概述' not in all_content_types:
        content_recs.append('发布品牌详细介绍、百科式内容')
    if '对比/评测' not in all_content_types:
        content_recs.append('制作竞品对比、评测类内容')
    if '教程/指南' not in all_content_types:
        content_recs.append('撰写行业教程、使用指南')
    if '排名/榜单' not in all_content_types:
        content_recs.append('参与或创建行业排名、推荐榜单')

    return recommendations, content_recs

# ============================= 蓝海关键词 =============================
def find_blue_ocean(results: List[dict], keyword: str) -> List[str]:
    """发现蓝海关键词（品牌少、来源单一的方向）"""
    blue_ocean = []
    total_brands = set()
    for r in results:
        for b in r.get('brands', []):
            total_brands.add(b['name'])

    if len(total_brands) <= 3:
        blue_ocean.append(f'「{keyword}」当前竞争品牌极少（{len(total_brands)}个），属于蓝海关键词')

    # 来源建议
    all_sources = set()
    for r in results:
        for cat in r.get('sources', {}):
            all_sources.add(cat)

    missing_sources = set(SOURCE_PATTERNS.keys()) - all_sources
    if '百科类' in missing_sources:
        blue_ocean.append('建议创建或优化百度百科/维基百科词条')
    if '问答社区' in missing_sources:
        blue_ocean.append('建议在知乎等平台布局问答内容')
    if '技术社区' in missing_sources:
        blue_ocean.append('建议在CSDN等平台发布技术文章')
    if '新闻媒体' in missing_sources:
        blue_ocean.append('建议通过新闻稿/媒体合作增加曝光')

    return blue_ocean[:5]


# ============================= 主函数 =============================
def analyze(keyword: str) -> dict:
    """完整分析流程"""
    start_time = time.time()
    all_pkeys = ['deepseek', 'kimi', 'qianwen', 'wenxin', 'zhipu', 'doubao', 'hunyuan']

    results = []
    ready_platforms = [(pkey, CFG['apis'][pkey]) for pkey in all_pkeys if _api_ready(pkey)]
    skipped = [pkey for pkey in all_pkeys if not _api_ready(pkey)]

    # 并发查询
    with concurrent.futures.ThreadPoolExecutor(max_workers=min(8, len(ready_platforms))) as executor:
        futures = {
            executor.submit(query_platform, pkey, keyword): cfg['name']
            for pkey, cfg in ready_platforms
        }
        for future in concurrent.futures.as_completed(futures, timeout=120):
            try:
                results.append(future.result())
            except Exception as e:
                name = futures[future]
                results.append({
                    'platform': name, 'pkey': '', 'success': False,
                    'error': str(e)[:150], 'response': '', 'brands': [],
                    'sources': {}, 'content_types': [], 'urls': [], 'response_length': 0,
                })

    # 添加跳过的平台
    for pkey in skipped:
        results.append({
            'platform': CFG['apis'][pkey].get('name', pkey), 'pkey': pkey,
            'success': False, 'error': 'API 未配置',
            'response': '', 'brands': [], 'sources': {}, 'content_types': [], 'urls': [],
            'response_length': 0,
        })

    # 分析
    difficulty = calculate_difficulty(results)
    recommendations, content_recs = generate_recommendations(results, difficulty)
    blue_ocean = find_blue_ocean(results, keyword)

    elapsed = round(time.time() - start_time, 1)

    return {
        'keyword': keyword,
        'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'elapsed_seconds': elapsed,
        'total_platforms': len(results),
        'success_count': sum(1 for r in results if r['success']),
        'platform_results': results,
        'difficulty': difficulty,
        'recommendations': recommendations,
        'content_recommendations': content_recs,
        'blue_ocean': blue_ocean,
    }


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': '请提供关键词'}, ensure_ascii=False))
        sys.exit(1)

    keyword = sys.argv[1]
    result = analyze(keyword)
    print(json.dumps(result, ensure_ascii=False, indent=2))
