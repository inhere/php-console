#compdef examples/app
# ------------------------------------------------------------------------------
#          DATE:  2019-01-07 10:34:20
#          FILE:  auto-completion.zsh
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  0.5.1
#   DESCRIPTION:  zsh shell complete for console app: examples/app
# ------------------------------------------------------------------------------
# usage: source auto-completion.zsh

_complete_for_examples_app () {
    local -a commands
    IFS=$'\n'
    commands=(
'version:Show application version information'
'help:Show application help information'
'list:List all group and alone commands'
'home:This is a demo command controller. there are some command usage examples(2) [alias\: h]'
'interact:there are some demo commands for use interactive method [alias\: iact]'
'phar:Pack a project directory to phar or unpack phar to directory'
'process:Some simple process to create and use examples [alias\: prc]'
'show:there are some demo commands for show format data'
'cor:a coroutine test command [alias\: coro]'
'demo:this is a demo alone command. but config use configure(), like symfony console\: argument define by position'
'exam:a description message'
'self-update:Update phar package to most recent stable, pre-release or development build. [alias\: selfUpdate]'
'test:this is a test independent command [alias\: t]'
    )

    _describe 'commands' commands
}

compdef _complete_for_examples_app examples/app
