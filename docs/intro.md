# 简介

简洁、功能全面的php命令行应用库。提供控制台参数解析, 颜色风格输出, 用户信息交互, 特殊格式信息显示。

> 无其他库依赖，可以方便的整合到任何已有项目中。

- 功能全面的命令行的选项参数解析(命名参数，短选项，长选项 ...)
- 命令行应用, 命令行的 `controller`, `command` 解析运行
- 命令行中功能强大的 `input`, `output` 管理、使用
- 消息文本的多种颜色风格输出支持(`info`, `comment`, `success`, `danger`, `error` ... ...)
- 丰富的特殊格式信息显示(`section`, `panel`, `padding`, `help-panel`, `table`, `title`, `list`, `progressBar`)
- 常用的用户信息交互支持(`select`, `confirm`, `ask/question`)
- 命令方法注释自动解析（提取为参数 `arguments` 和 选项 `options` 等信息）
- 类似 `symfony/console` 的预定义参数定义支持(按位置赋予参数值)
- 输出是 windows,linux 兼容的，不支持颜色的环境会自动去除相关CODE

> 下面所有的特性，效果都是运行 `examples/` 中的示例代码 `php examples/app` 展示出来的。下载后可以直接测试体验


## 项目地址

- **github** https://github.com/inhere/php-console.git
- **git@osc** https://git.oschina.net/inhere/php-console.git

**注意：**

- master 分支是要求 `php >= 7` 的(推荐使用)。
- php5 分支是支持 php5 `php >= 5.5` 的代码分支。

## 安装

- 使用 composer 命令

```bash
composer require inhere/console
```

- 使用 composer.json

编辑 `composer.json`，在 `require` 添加

```
"inhere/console": "dev-master",

// "inhere/console": "^2.0", // 指定稳定版本
// "inhere/console": "dev-php5", // for php5
```

然后执行: `composer update`

- 直接拉取

```
git clone https://git.oschina.net/inhere/php-console.git // git@osc
git clone https://github.com/inhere/php-console.git // github
```
