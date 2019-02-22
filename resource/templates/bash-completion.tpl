#!/usr/bin/env bash
# ------------------------------------------------------------------------------
#          DATE:  {{datetime}}
#          FILE:  auto-completion.bash
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  {{version}}
#   DESCRIPTION:  bash shell complete for console app: {{binName}}
# ------------------------------------------------------------------------------
# usage: source auto-completion.bash
# run 'complete' to see registered complete function.

_complete_for_{{fmtBinName}} () {
    local cur prev
    commands="{{commands}}"
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _complete_for_{{fmtBinName}} {{binName}}
