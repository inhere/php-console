#!/usr/bin/env bash
# ------------------------------------------------------------------------------
#          FILE:  auto-completion.bash
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  1.0.0
#   DESCRIPTION:  zsh shell complete for cli app: cliapp
# ------------------------------------------------------------------------------
# usage: source auto-completion.bash
# run 'complete' to see registered complete function.

_console_get_command_list () {
    php ./examples/app --no-color --only-name
}

_complete_for_cliapp () {
    local cur prev
    commands=(`_console_get_command_list`)
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _complete_for_cliapp examples/app
