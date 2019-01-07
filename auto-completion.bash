#!/usr/bin/env bash
# ------------------------------------------------------------------------------
#          DATE:  2019-01-07 10:16:23
#          FILE:  auto-completion.bash
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  0.5.1
#   DESCRIPTION:  bash shell complete for console app: examples/app
# ------------------------------------------------------------------------------
# usage: source auto-completion.bash
# run 'complete' to see registered complete function.

_complete_for_examples_app () {
    local cur prev
    commands="demo exam test self-update cor home process phar show interact version help list"
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _complete_for_examples_app examples/app
