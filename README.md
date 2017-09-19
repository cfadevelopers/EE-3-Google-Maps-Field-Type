# EE-3-Google-Maps-Field-Type
Add a google map to your CP. Allows you to geocode address and also populates latitudes and longitudes for any distance search etc.

This will only work with EE 3.* 
Make sure you have jQuery 
<ul>
    <li>Copy the files under system>user>addons</li>
    <li>Go to your Add-On Manager in CP</li>
    <li>Install TTK Google Maps</li>
</ul>

To use the Google Maps JavaScript API, you must register your app project on the Google API Console and get a Google API key which you can add to your app
<ul>
    <li>After you have installed it, head on to <a href="https://developers.google.com/maps/documentation/javascript/get-api-key">google console</a> and get an API Key.</li>
    <li>Open config.php file  add your api key to GOOGLE_MAP_API_KEY</li>
</ul>

<strong>Setup</strong>
Now if you go to create new field you will see a new type as Google maps. Select Google Maps give a sensible name like address then hit save. You also want to create two text input fields to store latitude and longitude right after this field. It should be in the following order.
<ul>
    <li>Address - Google Map</li>
    <li>Latitude - Text Input</li>
    <li>Longitude - Text Input</li>
</ul>
That is it, you should see a map when you publish entries.


