#!/usr/bin/env bash

_auto_completion()
{
	local cur prev
	_get_comp_words_by_ref -n = cur prev

	commands="commands list enumerate controller create-controller model \
	create-model all-models create-all-models project create-project scaffold \
	create-scaffold migration create-migration webtools create-webtools"

	case "$prev" in
		project|create-project)
			COMPREPLY=($(compgen -W "--name --webtools --directory --type --template-path --use-config-ini --trace --help --namespace" -- "$cur"))
			return 0
			;;
	esac

	COMPREPLY=($(compgen -W "$commands" -- "$cur"))

} &&
complete -F _auto_completion app
