GitHub Archive Access Challenge
================================

This is my response to the GitHub Archive leaderboard challenge located at
https://gist.github.com/scottburton11/844fdc53b6ef13387a01

Currently, there are more than 18 Event types according to https://developer.github.com/v3/activity/events/types/ .
So I created a method to check the page and retrieve the current list. A list can be stored in a text file and be a vailable to be compared against as well


The query is impacted mainly by the number of days between the after and before times as well as limit amount.
Checks on memory and time can be incorporated into the script


The output format of the data is decided upon internally by the displayFormat property but could easily be an additional argument.
This property decides which output method the final result array is sent to


Script is in PHP