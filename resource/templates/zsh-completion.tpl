#compdef {{binName}}
# ------------------------------------------------------------------------------
#          DATE:  {{datetime}}
#          FILE:  {{filename}}
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  {{version}}
#      HOMEPAGE:  https://github.com/inhere/php-console
#   DESCRIPTION:  zsh shell complete for console app: {{binName}}
# ------------------------------------------------------------------------------
#
# temp usage:
#   source {{filename}}
# add to ~/.zshrc:
#   source path/to/{{filename}}

_complete_for_{{fmtBinName}} () {
    local -a commands
    IFS=$'\n'
    commands+=(
{{commands}}
    )

    _describe 'commands' commands
}

compdef _complete_for_{{fmtBinName}} {{binName}}
