# PHP Console

[![License](https://img.shields.io/packagist/l/inhere/console.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=8.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/inhere/console)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/console.svg)](https://packagist.org/packages/inhere/console)
[![Github Actions Status](https://github.com/inhere/php-console/workflows/Unit-tests/badge.svg)](https://github.com/inhere/php-console/actions)
[![English](https://img.shields.io/badge/Readme-English-brightgreen.svg?maxAge=2592000)](README.en.md)

简洁、功能全面的php命令行应用库。提供控制台参数解析, 命令运行，颜色风格输出, 用户信息交互, 特殊格式信息显示。

> NOTICE: Current version **v4.1+**, require **PHP 8.0+**

## 命令行预览

![app-command-list](https://raw.githubusercontent.com/inhere/php-console/3.x/docs/screenshots/app-command-list.png)

## 功能概览

> 使用方便简单。可以方便的整合到任何已有项目中。

- 命令行应用, 命令行的 `controller`, `command` 解析运行 
- 支持给命令设置别名,一个命令可以有多个别名。
  - 支持命令的显示/隐藏，启用/禁用
- 命令行下的 输入`input`, 输出 `output`， Flag解析管理、使用
- 功能全面的命令行的选项、参数解析
  - 命名参数，数组参数
  - 选项绑定 短选项 `-s`，长选项 `--long`
  - **NEW** v4 支持定义数据类型(`bool,int,string,array`)，解析后会自动格式化输入值
- 命令方法注释自动解析为帮助信息
  - 默认提取 `@usage` `@arguments` `@options` `@example` 等信息
  - **NEW** v4 版本支持从注释定义选项参数的数据类型
- 支持注册事件监听，错误处理等

### 更多特性

- 支持输出多种颜色风格的消息文本(`info`, `comment`, `success`, `warning`, `danger`, `error` ... )
- 常用的特殊格式信息显示(`section`, `panel`, `padding`, `helpPanel`, `table`, `tree`, `title`, `list`, `multiList`)
- 丰富的动态信息显示(`pending/loading`, `pointing`, `spinner`, `counterTxt`, `dynamicText`, `progressTxt`, `progressBar`)
- 常用的用户信息交互支持(`select`, `multiSelect`, `confirm`, `ask/question`, `askPassword/askHiddenInput`)
- 支持类似 `symfony/console` 的预定义参数定义(按位置赋予参数值, 需要严格限制参数选项时推荐使用)
- 颜色输出是 `windows` `linux` `mac` 兼容的，不支持颜色的环境会自动去除相关CODE
- 快速的为当前应用生成 `bash/zsh` 环境下的自动补全脚本

### 内置工具

- 内置Phar打包工具类，可以方便的将应用打包成`phar`文件。方便分发和使用
  - 运行示例中的命令 `php examples/app phar:pack`,会将此console库打包成一个`app.phar`
- 内置了命令行下的文件下载工具类，带有进度条显示
- 命令行的php代码高亮支持（来自于`jakub-onderka/php-console-highlighter`并做了一些调整）
- 简单的Terminal屏幕、光标控制操作类
- 简单的进程操作使用类（fork,run,stop,wait ... 等）
  
所有的特性，功能：

 都可以运行 `examples/` 中的示例代码 `php examples/app` 展示出来的。基本上涵盖了所有功能，可以直接测试运行

## 项目地址

- **github** https://github.com/inhere/php-console.git
- **gitee** https://gitee.com/inhere/php-console.git

## 快速安装

- Requirement PHP 8.0+

```bash
composer require inhere/console
```

## 快速开始

```php
// file: examples/app
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

$meta = [
    'name'    => 'My Console App',
    'version' => '1.0.2',
];

// 通常无需传入 $input $output ，会自动创建
// $app = new \Inhere\Console\Application($meta, $input, $output);
$app = new \Inhere\Console\Application($meta);

// 注册命令
$app->command(DemoCommand::class);
// 注册命令组
$app->addGroup(MyController::class);
// ... ...

// run
$app->run();
```

运行示例应用： `php examples/app`

## 文档列表

**[从v3升级到v4](https://github.com/inhere/php-console/wiki/v3-upgrade-to-v4)**

- **[文档首页](https://github.com/inhere/php-console/wiki/home)**
- **[功能概览](https://github.com/inhere/php-console/wiki/overview)**
- **[安装](https://github.com/inhere/php-console/wiki/install)**
- **[创建应用](https://github.com/inhere/php-console/wiki/quick-start)**
- **[创建命令/组](https://github.com/inhere/php-console/wiki/create-command-and-group)**
- **[注册命令](https://github.com/inhere/php-console/wiki/register-command)**
- **[错误/异常捕获](https://github.com/inhere/php-console/wiki/error-handle)**

更多使用文档请点击跳转到[WIKI](https://github.com/inhere/php-console/wiki)查看

## 单元测试

```bash
phpunit
// 没有xdebug时输出覆盖率
phpdbg -dauto_globals_jit=Off -qrr /usr/local/bin/phpunit --coverage-text
```

## 开发调试

你可以通过环境变量 `CONSOLE_DEBUG=level`, 全局选项 `--debug level` 设置debug级别

```bash
# by ENV
$ CONSOLE_DEBUG=4 php examples/app
$ CONSOLE_DEBUG=5 php examples/app
# by global options
$ php examples/app --debug 4
```

## 使用console的项目

看看这些使用了 https://github.com/inhere/php-console 的项目:

- [kite](https://github.com/inhere/kite) PHP编写的，方便本地开发和使用的个人CLI工具应用
- More, please see [github used by](https://github.com/inhere/php-console/network/dependents?package_id=UGFja2FnZS01NDI5NzMxOTI%3D)

## 我的其他项目

- [inhere/php-validate](https://github.com/inhere/php-validate) 一个简洁小巧且功能完善的php验证库
- [inhere/sroute](https://github.com/inhere/php-srouter) 轻量且快速的HTTP请求路由库

## 依赖包

- [toolkit/cli-utils](https://github.com/php-toolkit/cli-utils)
- [toolkit/pflag](https://github.com/php-toolkit/pflag)
- [toolkit/stdlib](https://github.com/php-toolkit/stdlib)
- [toolkit/sys-utils](https://github.com/php-toolkit/sys-utils)

## License

[MIT](LICENSE)
