# 说明

一些辅助命令及脚本文件。 

来自于： 

- ConEmu https://github.com/Maximus5/ConEmu

## 使用

### 在cmd里输出颜色：

需要设置一个环境变量 `set ESC=`, 对应的 ASCII `\x1B`

先运行：

```bash
call SetEscChar.cmd
```

然后执行：

```bash
echo %ESC%[1;33;40m Yellow on black %ESC%[0m
```

成功的话，就会看见黄色的文字 <span style="color: yellow;">Yellow on black</span>

### 设置cmd中的字符集为 `utf-8`

```bash
chcp 65001 & cmd
```



## 参考

- https://conemu.github.io/en/AnsiEscapeCodes.html