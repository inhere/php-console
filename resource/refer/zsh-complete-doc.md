# zsh complete

参考补全代码仓库提供的文档：

https://github.com/zsh-users/zsh-completions/blob/master/zsh-completions-howto.org

补全代码示例：

https://github.com/zsh-users/zsh-completions/blob/master/src/_golang

重新加载定义的补全函数：

```bash
unfunction _pandoc && autoload -U _pandoc
```
