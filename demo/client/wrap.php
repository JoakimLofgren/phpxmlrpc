<html>
<head><title>xmlrpc - Webservice wrappper demo</title></head>
<body>
<h1>Webservice wrappper demo</h1>

<h2>Wrap methods exposed by server into php functions</h2>

<h3>The code demonstrates usage of some the most automagic client usage possible:<br/>
    1) client that returns php values instead of xmlrpc value objects<br/>
    2) wrapping of remote methods into php functions<br/>
    See also proxy.php for an alternative take
</h3>
<?php

include_once __DIR__ . "/../../src/Autoloader.php";
PhpXmlRpc\Autoloader::register();

$client = new PhpXmlRpc\Client("http://phpxmlrpc.sourceforge.net/server.php");
$client->return_type = 'phpvals'; // let client give us back php values instead of xmlrpcvals
$resp = $client->send(new PhpXmlRpc\Request('system.listMethods'));
if ($resp->faultCode()) {
    echo "<p>Server methods list could not be retrieved: error {$resp->faultCode()} '" . htmlspecialchars($resp->faultString()) . "'</p>\n";
} else {
    $testCase = '';
    $wrapper = new PhpXmlRpc\Wrapper();
    echo "<p>Server methods list retrieved, now wrapping it up...</p>\n<ul>\n";
    foreach ($resp->value() as $methodName) {
        // $resp->value is an array of strings

        // do not wrap remote server system methods
        if (strpos($methodName, 'system.') !== 0) {
            $funcName = $wrapper->wrap_xmlrpc_method($client, $methodName);
            if ($funcName) {
                echo "<li>Remote server method " . htmlspecialchars($methodName) . " wrapped into php function " . $funcName . "</li>\n";
            } else {
                echo "<li>Remote server method " . htmlspecialchars($methodName) . " could not be wrapped!</li>\n";
            }
            if ($methodName == 'examples.getStateName') {
                $testCase = $funcName;
            }
        }
    }
    echo "</ul>\n";
    if ($testCase) {
        echo "Now testing function $testCase: remote method to convert U.S. state number into state name";
        $stateNum = rand(1, 51);
        $stateName = $testCase($stateNum, 2);
        echo "State number $stateNum is " . htmlspecialchars($stateName);
    }
}
?>
</body>
</html>
