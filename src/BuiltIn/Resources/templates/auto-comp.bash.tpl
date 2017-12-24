#!/usr/bin/env bash

#
# This is auto completion script for bash env.
# Auto generate by inhere/console.
# application name: {$name}
# @date {$datetime}
#

_{$name}_auto_completion()
{
	local cur prev
	_get_comp_words_by_ref -n = cur prev

	commands="{$commands}"

	case "$prev" in
		project|create-project)
			COMPREPLY=($(compgen -W "--name --webtools --directory --type --template-path --use-config-ini --trace --help --namespace" -- "$cur"))
			return 0
			;;
	esac

	COMPREPLY=($(compgen -W "$commands" -- "$cur"))

} &&
complete -F _{$name}_auto_completion {$name}
