#compdef examples/app
# ------------------------------------------------------------------------------
#          FILE:  auto-completion.zsh
#        AUTHOR:  inhere (https://github.com/inhere)
#       VERSION:  1.0.0
#   DESCRIPTION:  zsh shell complete for cli app: cliapp
# ------------------------------------------------------------------------------
# usage: source auto-completion.zsh

_console_get_command_list () {
    IFS=" "
    php ./examples/app --no-color | \
        sed "1,/Available Commands/d" | \
        awk '/  [a-z]+/ { print $0 }' | \
        sed -E 's/^[ ]+//g' | \
        sed -E 's/[:]+/\\:/g' | \
        sed -E 's/[ ]{2,}/\:/g'
}

_console () {
    local -a commands
    IFS=$'\n'
    commands=(`_console_get_command_list`)
#    commands="$commands\nhelp:Show application help information"
    _describe 'commands' commands
}

compdef _console php examples/app
compdef _console examples/app
