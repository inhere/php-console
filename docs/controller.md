# 注册命令

### 注册独立命令
### 注册组命令
### 设置命令名称
### 设置命令描述

## 独立命令

## 组命令(controller)

## 输入定义(InputDefinition)


## 设置参数

### 使用名称设置参数

### 根据位置设置参数

```
$ php examples/app demo john male 43 --opt1 value1 -y
hello, this in Inhere\Console\examples\DemoCommand::execute
this is argument and option example:
                                        the opt1's value
                                option: opt1 |
                                     |       |
php examples/app demo john male 43 --opt1 value1 -y
        |         |     |    |   |                |
     script    command  |    |   |______   option: yes, it use shortcat: y, and it is a Input::OPT_BOOLEAN, so no value.
                        |    |___       |
                 argument: name  |   argument: age
                            argument: sex
```
