#!/bin/sh

# Is mutagen causing the files inside docker to not be in sync?
# Fear not, friend! Just run me and I'll make your dreams come true.

mutagen daemon stop
mutagen daemon start
