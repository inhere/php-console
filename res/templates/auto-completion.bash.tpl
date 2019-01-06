#!/usr/bin/env bash
# ------------------------------------------------------------------------------
#          DATE:  {{datetime}}
#          FILE:  auto-completion.bash
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  1.0.0
#   DESCRIPTION:  bash shell complete for cli app: cliapp
# ------------------------------------------------------------------------------
# usage: source auto-completion.bash
# run 'complete' to see registered complete function.

_complete_for_cliapp () {
    local cur prev
    commands= "{{commands}}"
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _complete_for_cliapp examples/app
