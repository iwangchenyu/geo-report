#!/bin/bash
# GEO 报表系统 - 定时爬取脚本
# 用法: 添加到 crontab
#   0 8,14,20 * * * /bin/bash /Users/wangshifu/Desktop/wangchenyu/baobiao/crawl_cron.sh
#
# 或在终端手动运行: bash crawl_cron.sh

cd "$(dirname "$0")"
LOG="data/crawl_cron.log"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] 定时爬取开始..." >> "$LOG"
python3 crawler_v4.py --crawl --json >> "$LOG" 2>&1
echo "[$(date '+%Y-%m-%d %H:%M:%S')] 定时爬取结束" >> "$LOG"
