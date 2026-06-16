#!/usr/bin/env python3
"""
GEO 报表系统 - AI平台收录自动爬虫 v4 (全API版)
================================================
8大AI平台全部通过官方 API 直调，零浏览器依赖:
  DeepSeek, Kimi, 通义千问, 文心一言, 智谱AI, 豆包, 腾讯元宝/混元, 纳米AI/360

核心特性:
  - 全 API 直调，速度快、稳定、无需维护浏览器登录态
  - 品牌变体命中检测: 检查回复中是否出现公司全称/简称
  - 8平台并发请求 (ThreadPoolExecutor)
  - 截图存档 (可选, 需 Playwright)
  - 与现有 SQLite + PHP 前端完全兼容

使用方式:
  python3 crawler_v4.py --crawl              # 全量爬取
  python3 crawler_v4.py --crawl -k "科大"    # 指定关键词
  python3 crawler_v4.py --crawl -p deepseek  # 指定平台
  python3 crawler_v4.py --status             # 查看状态
"""

import os, sys, re, json, time, sqlite3, hashlib, random
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Tuple, Optional
import concurrent.futures

import yaml
import requests

# ---- 路径 & 基础配置 ----
BASE_DIR = Path(__file__).resolve().parent
DB_PATH = BASE_DIR / 'data' / 'report.db'
SCREENSHOT_DIR = BASE_DIR / 'data' / 'screenshots'
CONFIG_PATH = BASE_DIR / 'config.yaml'
TOKEN_CACHE = BASE_DIR / 'data' / '.wenxin_token.json'
SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)

# ---- 加载配置 ----
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

# ---- 公司名称变体 ----
COMPANY_FULL = CFG['company']['full_name']
COMPANY_VARIANTS = CFG['company']['variants']
ALL_COMPANY_NAMES = [COMPANY_FULL] + COMPANY_VARIANTS

# ===================================================================
#  数据库操作
# ===================================================================
def get_db():
    conn = sqlite3.connect(str(DB_PATH))
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")
    return conn

def get_keywords(conn):
    return conn.execute("SELECT id, name, type, query_text FROM keywords").fetchall()

def get_platforms(conn):
    return conn.execute("SELECT id, name, pkey FROM platforms ORDER BY sort_order").fetchall()

def delete_existing_reports(conn, keyword_id, platform_id):
    conn.execute("DELETE FROM reports WHERE keyword_id=? AND platform_id=?", 
                 (keyword_id, platform_id))
    conn.commit()

def save_report(conn, keyword_id, platform_id, keyword, question,
                inclusion_date, share_url, response_text, response_snippet, 
                matched, hit_names=None):
    conn.execute("""
        INSERT INTO reports (keyword_id, platform_id, question, is_mobile,
            inclusion_date, share_url, response_text, response_snippet, matched, hit_names)
        VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
    """, (keyword_id, platform_id, question, inclusion_date, share_url,
          response_text, response_snippet, 1 if matched else 0,
          ','.join(hit_names) if hit_names else ''))
    conn.commit()

# ===================================================================
#  品牌变体命中检测
# ===================================================================
def check_company_mention(response_text: str) -> Tuple[bool, List[str]]:
    if not response_text or len(response_text) < 10:
        return False, []
    hits = []
    for name in ALL_COMPANY_NAMES:
        if name in response_text:
            hits.append(name)
    return len(hits) > 0, hits

# ===================================================================
#  API 通用调用
# ===================================================================
def _api_call(base_url: str, api_key: str, model: str, prompt: str,
              temperature=None, max_tokens=None) -> str:
    """通用 OpenAI 兼容格式 API 调用"""
    temp = temperature if temperature is not None else CFG['crawl']['temperature']
    mt = max_tokens if max_tokens is not None else CFG['crawl']['max_tokens']
    headers = {
        'Authorization': f'Bearer {api_key}',
        'Content-Type': 'application/json',
    }
    payload = {
        'model': model,
        'messages': [{'role': 'user', 'content': prompt}],
        'temperature': temp,
        'max_tokens': mt,
    }
    resp = requests.post(base_url, json=payload, headers=headers, timeout=90)
    resp.raise_for_status()
    return resp.json()['choices'][0]['message']['content']

# ===================================================================
#  文心一言 Token 管理
# ===================================================================
def _get_wenxin_token() -> str:
    cfg = CFG['apis']['wenxin']
    if TOKEN_CACHE.exists():
        try:
            cache = json.loads(TOKEN_CACHE.read_text())
            if cache.get('expires_at', 0) > time.time() + 86400:
                return cache['access_token']
        except:
            pass
    params = {
        'grant_type': 'client_credentials',
        'client_id': cfg['api_key'],
        'client_secret': cfg['secret_key'],
    }
    resp = requests.post(
        'https://aip.baidubce.com/oauth/2.0/token',
        params=params, timeout=30)
    resp.raise_for_status()
    data = resp.json()
    token = data['access_token']
    expires_in = data.get('expires_in', 2592000)
    TOKEN_CACHE.write_text(json.dumps({
        'access_token': token,
        'expires_at': time.time() + expires_in,
    }))
    return token

def _api_call_wenxin(prompt: str) -> str:
    token = _get_wenxin_token()
    url = ('https://aip.baidubce.com/rpc/2.0/ai_custom/v1/'
           f'wenxinworkshop/chat/completions_pro?access_token={token}')
    payload = {'messages': [{'role': 'user', 'content': prompt}]}
    resp = requests.post(url, json=payload, timeout=90)
    resp.raise_for_status()
    return resp.json()['result']

# ===================================================================
#  各平台 API 查询函数
# ===================================================================
def query_deepseek(prompt: str) -> str:
    cfg = CFG['apis']['deepseek']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)

def query_kimi(prompt: str) -> str:
    cfg = CFG['apis']['kimi']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)

def query_qianwen(prompt: str) -> str:
    cfg = CFG['apis']['qianwen']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)

def query_wenxin(prompt: str) -> str:
    return _api_call_wenxin(prompt)

def query_zhipu(prompt: str) -> str:
    cfg = CFG['apis']['zhipu']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)

def query_doubao(prompt: str) -> str:
    cfg = CFG['apis']['doubao']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)

def query_hunyuan(prompt: str) -> str:
    cfg = CFG['apis']['hunyuan']
    return _api_call(cfg['base_url'], cfg['api_key'], cfg['model'], prompt)



# ---- API 查询函数映射 ----
API_QUERY_FUNCS = {
    'deepseek': query_deepseek,
    'kimi': query_kimi,
    'qianwen': query_qianwen,
    'wenxin': query_wenxin,
    'zhipu': query_zhipu,
    'doubao': query_doubao,
    'hunyuan': query_hunyuan,
}

# ===================================================================
#  API 可用性检查
# ===================================================================
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

# ===================================================================
#  截图存档 (可选, 使用 Playwright)
# ===================================================================
def save_screenshot_html(response_text: str, keyword: str, platform: str) -> Optional[str]:
    if not CFG['crawl'].get('screenshot_enabled', True):
        return None
    try:
        from playwright.sync_api import sync_playwright
    except ImportError:
        return None

    ts = int(time.time())
    hash_id = hashlib.md5(f'{keyword}{platform}{ts}'.encode()).hexdigest()[:8]
    safe_platform = re.sub(r'[^a-zA-Z0-9_]', '_', platform)
    safe_keyword = re.sub(r'[^a-zA-Z0-9\u4e00-\u9fff_]', '_', keyword)[:30]
    filename = f'{safe_platform}_{ts}_{hash_id}_{safe_keyword}.png'
    filepath = SCREENSHOT_DIR / filename

    escaped = (response_text
               .replace('&', '&amp;').replace('<', '&lt;')
               .replace('>', '&gt;').replace('\n', '<br>'))

    html = f'''<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{{font-family:"PingFang SC","Microsoft YaHei",sans-serif;padding:24px;max-width:860px;line-height:1.85;color:#1d2129}}
.header{{color:#9ca3af;font-size:13px;margin-bottom:16px;border-bottom:1px solid #e5e7eb;padding-bottom:12px}}
.header strong{{color:#374151}}
.content{{font-size:15px;white-space:pre-wrap;word-break:break-word}}
</style></head><body>
<div class="header">平台: <strong>{platform}</strong> | 关键词: <strong>{keyword}</strong> | 时间: <strong>{datetime.now().strftime("%Y-%m-%d %H:%M:%S")}</strong></div>
<div class="content">{escaped}</div>
</body></html>'''

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page(viewport={'width': 900, 'height': 600})
            page.set_content(html, timeout=15000)
            page.screenshot(path=str(filepath), full_page=True)
            browser.close()
        return str(filepath)
    except Exception:
        return None

# ===================================================================
#  单平台单关键词查询
# ===================================================================
def query_single(platform_pkey: str, platform_name: str, 
                 keyword: str, query_text: str) -> dict:
    """对单个平台查询一个关键词，返回完整结果"""
    prompt_tpl = CFG['crawl']['prompt_template']
    prompt = (query_text.strip() if query_text and query_text.strip()
              else prompt_tpl.format(keyword=keyword))

    result = {
        'success': False, 'response_text': '', 'response_snippet': '',
        'share_url': '', 'matched': False, 'hit_names': [],
        'error': '', 'method': 'api',
    }

    if not _api_ready(platform_pkey):
        result['error'] = 'API 未配置'
        return result

    query_func = API_QUERY_FUNCS.get(platform_pkey)
    if not query_func:
        result['error'] = '未找到查询函数'
        return result

    retry_max = CFG['crawl']['retry_max']
    for attempt in range(retry_max):
        try:
            time.sleep(random.uniform(0.3, 1.2))
            response_text = query_func(prompt)
            result['response_text'] = response_text
            result['response_snippet'] = response_text[:300]
            result['success'] = True
            break
        except Exception as e:
            err_str = str(e)
            if '429' in err_str and attempt < retry_max - 1:
                wait = (2 ** attempt) + random.uniform(0, 1)
                time.sleep(wait)
            else:
                result['error'] = f"{err_str[:150]}"

    if result['success']:
        matched, hits = check_company_mention(result['response_text'])
        result['matched'] = matched
        result['hit_names'] = hits

    return result

# ===================================================================
#  全量爬取主函数
# ===================================================================
def crawl_all(conn, keyword_filter=None, platform_filter=None, clear_existing=True):
    keywords = get_keywords(conn)
    platforms = get_platforms(conn)

    if keyword_filter:
        keywords = [k for k in keywords if keyword_filter in k[1]]
    if platform_filter:
        platforms = [p for p in platforms if p[2] == platform_filter]

    total = len(keywords) * len(platforms)
    current = [0]
    results = []

    print(f"\n{'='*60}")
    print(f"GEO 爬虫 v4 (全API): {len(keywords)} 关键词 x {len(platforms)} 平台 = {total} 次查询")
    print(f"公司名称检测: {ALL_COMPANY_NAMES}")
    api_ready_count = sum(1 for p in platforms if _api_ready(p[2]))
    print(f"API 已配置: {api_ready_count}/{len(platforms)}")
    print(f"{'='*60}\n")

    # 对每个平台并发查询所有关键词
    for plat_id, plat_name, plat_pkey in platforms:
        if not _api_ready(plat_pkey):
            print(f"⚠️ {plat_name}: API 未配置，跳过")
            continue

        api_name = CFG['apis'][plat_pkey].get('name', plat_name)
        print(f"\n📡 [{api_name}] 开始查询 {len(keywords)} 个关键词...")

        with concurrent.futures.ThreadPoolExecutor(
                max_workers=min(CFG['crawl']['concurrent_workers'], len(keywords))) as executor:
            future_map = {}
            for kw_id, kw_name, kw_type, kw_query_text in keywords:
                future = executor.submit(
                    query_single, plat_pkey, plat_name, kw_name, kw_query_text)
                future_map[future] = (kw_id, kw_name, kw_type, kw_query_text)

            for future in concurrent.futures.as_completed(future_map, timeout=120):
                kw_id, kw_name, kw_type, kw_query_text = future_map[future]
                current[0] += 1
                try:
                    data = future.result()
                except Exception as e:
                    data = {'success': False, 'error': str(e), 'matched': False,
                            'response_text': '', 'response_snippet': '',
                            'hit_names': [], 'method': 'error'}

                prompt_tpl = CFG['crawl']['prompt_template']
                question = (kw_query_text.strip() if kw_query_text and kw_query_text.strip()
                            else prompt_tpl.format(keyword=kw_name))
                inclusion_date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                if clear_existing:
                    delete_existing_reports(conn, kw_id, plat_id)

                if data['success']:
                    save_report(conn, kw_id, plat_id, kw_name, question,
                                inclusion_date, '', data['response_text'],
                                data['response_snippet'], data['matched'],
                                data.get('hit_names'))

                    screenshot_path = save_screenshot_html(
                        data['response_text'], kw_name, plat_name)

                    status = '✅ 提及' if data['matched'] else '❌ 未提及'
                    hit_info = f" (命中: {', '.join(data['hit_names'])})" if data['hit_names'] else ''
                    print(f"  [{current[0]}/{total}] {kw_name} {status}{hit_info}")
                    results.append({
                        'keyword': kw_name, 'platform': plat_name,
                        'matched': data['matched'], 'success': True,
                        'hit_names': data['hit_names'],
                        'screenshot': screenshot_path,
                    })
                else:
                    print(f"  [{current[0]}/{total}] {kw_name} ⚠️ {data['error']}")
                    results.append({
                        'keyword': kw_name, 'platform': plat_name,
                        'matched': False, 'success': False,
                        'error': data['error'],
                    })

    # ---- 汇总 ----
    total_success = sum(1 for r in results if r['success'])
    total_matched = sum(1 for r in results if r.get('matched'))

    print(f"\n{'='*60}")
    print(f"📊 爬取完成! 总计 {total}, 成功 {total_success}, 品牌提及 {total_matched}")

    for plat_name in sorted(set(r['platform'] for r in results)):
        pr = [r for r in results if r['platform'] == plat_name]
        matched = sum(1 for r in pr if r.get('matched'))
        print(f"  {plat_name}: {matched}/{len(pr)} 提及")

    return {
        'total': total, 'total_success': total_success,
        'total_matched': total_matched, 'results': results,
        'company_names': ALL_COMPANY_NAMES,
        '_timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
    }

# ===================================================================
#  状态面板
# ===================================================================
def status_mode():
    print("\n📋 平台状态 (全API模式):")
    print("-" * 80)
    print(f"{'平台':<16} {'API Key':<12} {'模型':<22} {'状态':<10} {'获取Key'}")
    print("-" * 80)

    for pkey in ['deepseek', 'kimi', 'qianwen', 'wenxin', 'zhipu', 'doubao', 'hunyuan']:
        api_cfg = CFG['apis'].get(pkey, {})
        name = api_cfg.get('name', pkey)
        api_ok = _api_ready(pkey)
        model = api_cfg.get('model', '---')
        key_status = '✅ 已配置' if api_ok else '❌ 未配置'
        overall = '✅ 可用' if api_ok else '⏳'
        key_url = api_cfg.get('key_url', '')
        print(f"{name:<16} {key_status:<12} {model:<22} {overall:<10} {key_url}")
    print("-" * 80)
    print(f"\n公司名称检测: {ALL_COMPANY_NAMES}")
    print(f"配置文件: {CONFIG_PATH}")
    print("\n💡 设置 API Key:")
    print("   export DEEPSEEK_API_KEY=sk-xxx")
    print("   export KIMI_API_KEY=sk-xxx")
    print("   export DASHSCOPE_API_KEY=sk-xxx")
    print("   export WENXIN_API_KEY=xxx   && export WENXIN_SECRET_KEY=xxx")
    print("   export ZHIPU_API_KEY=xxx")
    print("   export DOUBAO_API_KEY=xxx   && export DOUBAO_ENDPOINT_ID=xxx")
    print("   export HUNYUAN_API_KEY=xxx")
    print("   export NANO360_API_KEY=xxx")

# ===================================================================
#  CLI
# ===================================================================
if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description='GEO AI平台收录爬虫 v4 (全API)')
    parser.add_argument('--crawl', action='store_true', help='全量爬取 (全API)')
    parser.add_argument('--status', '-s', action='store_true', help='查看平台状态')
    parser.add_argument('--platform', '-p', type=str, help='指定平台 pkey')
    parser.add_argument('--keyword', '-k', type=str, help='指定关键词')
    parser.add_argument('--no-clear', action='store_true', help='不清除已有数据')
    parser.add_argument('--json', action='store_true', help='JSON 输出')

    args = parser.parse_args()

    if args.crawl:
        conn = get_db()
        try:
            summary = crawl_all(conn,
                keyword_filter=args.keyword,
                platform_filter=args.platform,
                clear_existing=not args.no_clear)
            if args.json:
                print(json.dumps(summary, ensure_ascii=False, indent=2))
        finally:
            conn.close()
    else:
        status_mode()
        if not args.status:
            print("\n使用方式:")
            print("  python3 crawler_v4.py --crawl   # 全量爬取 (8平台并发API)")
            print("  python3 crawler_v4.py --status  # 查看状态")
