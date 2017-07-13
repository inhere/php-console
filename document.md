
## 根据位置设置参数

```
$ php examples/app demo john male 43 --opt1 value1 -y
hello, this in inhere\console\examples\DemoCommand::execute
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
