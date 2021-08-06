#!/usr/bin/env bash
# ------------------------------------------------------------------------------
#          DATE:  {{datetime}}
#          FILE:  {{filename}}
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  {{version}}
#      HOMEPAGE:  https://github.com/inhere/php-console
#   DESCRIPTION:  bash shell complete for console app: {{binName}}
# ------------------------------------------------------------------------------
#
# temp usage:
#   source {{filename}}
# add to ~/.bashrc:
#   source path/to/{{filename}}
# run 'complete' to see registered complete function.

_complete_for_{{fmtBinName}} () {
    local cur prev
    commands="{{commands}}"
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _complete_for_{{fmtBinName}} {{binName}}
