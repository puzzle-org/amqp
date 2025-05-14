#------------------------------------------------------------------------------
# Karma
#------------------------------------------------------------------------------
config: karma ## Run karma for behat tests
	./karma hydrate -e behat

karma:
	wget -O karma -q https://github.com/Niktux/karma/releases/download/5.6.0/karma.phar
	chmod 0755 karma

clean-karma:
	rm -f karma

.PHONY: config clean-karma
