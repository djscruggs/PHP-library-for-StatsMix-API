This is a PHP library for the StatsMix api - see http://www.statsmix.com/developers

## What is StatsMix?

StatsMix makes it easy to track, chart, and share application and business metrics. Use StatsMix to:

* Log every time a particular event happens (such as a user creating a new blog post)
* View a real-time chart of these application events in StatsMix's web UI
* Share the charts with users inside and outside your organization
* Create and share custom dashboards that aggregate multiple metrics together
    * Example dashboard: http://www.statsmix.com/d/0e788d59208900e7e3bc
    * Example embedded dashboard: http://www.statsmix.com/example-embedded

To get started, you'll need a API key for StatsMix. You can get a free developer account here: http://www.statsmix.com/try?plan=developer

## Quick Start

You can copy & paste the example below. Just make sure to change the API key.

	//in your code
	require "StatsMix.php";
	StatsMix::set_api_key("YOUR API KEY");

	//the basic format:
	StatsMix::track($name_of_metric,$value = 1,$options = array());

	//push a stat with the value 1 (default) to a metric called "My First Metric"
	StatsMix::track("My First Metric");

	//push the value 20
	StatsMix::track("My First Metric",20);

	/**
	 * Add metadata via the "meta" option in $options - you can use this to add granularity to your chart via Chart Settings in StatsMix.
	 * This example tracks file uploads by file type.
	 */
	StatsMix::track("File Uploads", 1, array('meta' => array("file type" => "PDF")));

	/** 
	 *	If you need the ability to update a stat after the fact (i.e. you're updating the same stat several times a day), 
	 *  you can pass in a unique identifier called ref_id, which is scoped to the metric (i.e. you can use the same identifier across metrics)
	 *  This example uses the current date (in UTC time) for ref_id
	 */
	StatsMix::track("File Uploads", 1, array('ref_id' => gmstrftime('%Y-%m-%d'), 'meta' => array("file type" => "PDF")));

	//if you need to timestamp the stat for something other than now, pass in a UTC datetime called generated_at
	StatsMix::track("File Uploads", 1, array('generated_at' => gmstrftime('%Y-%m-%d %H:%I:%S',strtotime('yesterday'))));

	//to turn off tracking in your development environment
	StatsMix::set_ignore(true);

	//to redirect all stats in dev environment to a test metric
	StatsMix::set_test_metric_name("My Test Metric");

	//if you have multiple profiles in your account, specify which one via profile_id
	StatsMix::track("metric name that may be in multiple profiles", 1, array('profile_id' => "PROFILE_ID"));


To create metrics and stats using a more OO approach, check out the classes SmMetric and SmStat in StatsMix.php. Using them you can do things like this:

	//create a metric
	$metric = new SmMetric;
	$metric->name = "My Test Metric";
	$metric->save();
	if($metric->error){
		echo "<p>Error: {$metric->error}</p>";
	}
	//view the xml response
	echo $metric->get_response();

## More Documentation

The StatsMix PHP Library supports all the methods documented at http://www.statsmix.com/developers/documentation



## Contributing to statsmix
 
* Check out the latest master to make sure the feature hasn't been implemented or the bug hasn't been fixed yet
* Check out the issue tracker to make sure someone already hasn't requested it and/or contributed it
* Fork the project
* Start a feature/bugfix branch
* Commit and push until you are happy with your contribution
* Make sure to add tests for it. This is important so I don't break it in a future version unintentionally.
* Please try not to mess with the Rakefile, version, or history. If you want to have your own version, or is otherwise necessary, that is fine, but please isolate to its own commit so I can cherry-pick around it.


### Copyright

Copyright (c) 2011 StatsMix, Inc. See LICENSE.txt for further details.