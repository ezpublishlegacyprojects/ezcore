<?php /* #?ini charset="utf-8"?

[EditSettings]
ExtensionDirectories[]=ezcore


[HideSettings]
# List of root nodes where nodes can be hidden / unhidden.
RootNodeList[]
RootNodeList[]=2
# Specifies if cronjob / content publish handler should modify the publish time on the object when
# object is unhidden ("published")
# Warning: The cronjob will have no idea that a object is previusly published (unhidden) if this is disabled.
UnhideModifyPublishDate=enabled

# Hide / Unhide examples:
## Class attribute map, describing which date/time fields to use.
## The systems uses the class identifier to determine which classes to unhide in the cronjob.
# UnhideDateAttributeList[]
# UnhideDateAttributeList[article]=publish_date
#
## Class attribute map, describing which date/time fields to use.
## The systems uses the class identifier to determine which classes to hide in the cronjob.
# HideDateAttributeList[]
# HideDateAttributeList[article]=hide_date




*/ ?>