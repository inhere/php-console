#compdef {{binName}}
# ------------------------------------------------------------------------------
#          DATE:  {{datetime}}
#          FILE:  auto-completion.zsh
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  {{version}}
#   DESCRIPTION:  zsh shell complete for console app: {{binName}}
# ------------------------------------------------------------------------------
# usage: source auto-completion.zsh

_complete_for_{{fmtBinName}} () {
    local -a commands
    IFS=$'\n'
    commands=(
{{commands}}
    )

    _describe 'commands' commands
}

compdef _complete_for_{{fmtBinName}} {{binName}}
