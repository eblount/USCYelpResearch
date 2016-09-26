<?php

include 'vendor/autoload.php';

use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

// Create a new Yelp Client
$yelp = new Stevenmaguire\Yelp\Client([
    'consumerKey' => 'YELP_CONSUMER_KEY',
    'consumerSecret' => 'YELP_CONSUMER_SECRET',
    'token' => 'YELP_TOKEN',
    'tokenSecret' => 'YELP_TOKEN_SECRET',
    'apiHost' => 'api.yelp.com' // Optional, default 'api.yelp.com'
]);

// Build the search
$search = [
    'term' => 'Vape Shops',
    //'category_filter' => 'vapeshops',
    'location' => 'Los Angeles, CA',
    'limit' => 20,
];

// Add a search for PokeStops if the command line argument is 'stops'
if (!empty($argv[1]) && $argv[1] == 'stops') {
    $search['term'] .= ' PokeStop';
}

// Set up pagination variables
$total_retrieved = 0;
$offset = 0;

// Search the Yelp API
$results = $yelp->search($search);

// Get the total number of businesses
$total_businesses = $results->total;

// Set up an array to store formatted result entries, with the header row as the first entry
$output = [
    [
        'Yelp ID',
        'Name',
        'Address',
        'Phone',
        'Latitude',
        'Longitude',
        'Rating',
        'Reviews',
        'Yelp URL',
        'Closed',
    ]
];

// Process the results of each API call as long as there are results left in the result set
do {
    // In case the API returns no results, break out of this loop so we don't run indefinitely
    if (!count($results->businesses)) {
        break;
    }

    // Loop through each business result and build the output entries
    foreach ($results->businesses as $business) {
        $entry = [
            'id' => $business->id ?? '',
            'name' => $business->name ?? '',
            'address' => implode(', ', $business->location->display_address ?? []),
            'phone' => $business->phone ?? '',
            'latitude' => $business->location->coordinate->latitude ?? '',
            'longitude' => $business->location->coordinate->longitude ?? '',
            'rating' => $business->rating ?? '',
            'reviews' => $business->review_count ?? '',
            'yelpurl' => $business->url ?? '',
            'closed' => (!empty($business->is_closed)) ? 'yes' : 'no',
        ];

        // Add the entry to the output results
        $output[] = $entry;
    }

    // Increment the retrieved by the number of businesses in this result set
    $total_retrieved += count($results->businesses);

    // Increment the offset for the next API call
    $offset += 20;
    $search['offset'] = $offset;

    // Call the API again with the new offset
    $results = $yelp->search($search);
} while ($total_retrieved < $total_businesses);

// Set up the CSV exporter
$config = new ExporterConfig();
$exporter = new Exporter($config);

// Export the output results to CSV
$exporter->export('php://output', $output);
