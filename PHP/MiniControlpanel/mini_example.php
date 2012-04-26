<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Mini control panel</title>
    </head>
    <body>
        <?php
        $config = array(
	    "username" => "CL12345",        // Your username used to log on to http://customer.glesys.com
            "local password" => "LOSENORD", // A local password, used only for this script.
            "serverid" => "vzXXXXXX",      // The ID of the server to be controlled. starts with vz or xm. 
                                           // Can be found when you edit an server at http://customer.glesys.com
            "API-key" => "abc123abc123"    // Your API-key. Can be generated at http://customer.glesys.com
        );
        include 'APIClient.php';
        try{
            $client = new APIClient($config["username"], $config["API-key"]);
        } catch(Exception $e){
            echo "Connection error: ".$e->getMessage();
            return;
        }
        if(isset($_GET["pass"]) and isset($_GET["cmd"]))
        {
            if($_GET["pass"] == $config["local password"])
            {
                if($_GET["cmd"] == "reboot")
                    reboot();
                else
                    echo"Unknown command.";
            }
            else
                echo "Wrong password";
        }
        else
        {
            ?>
	    <form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
        	Password <input type="password" name="pass"><br/>
	        <input type="submit" name="cmd" value="reboot"/>
            </form>
	    <?php
        }
        // Reboots the server.
        function reboot()
        {
            global $client;
            global $config;
            try{
                $client->post("server/stop", array("serverid" => $config["serverid"], "type" => "reboot"));
            } catch(Exception $e){
                echo "Connection error: ".$e->getMessage();
                return;
            }
            echo"The server was restarted succesfully.";
        }
        ?>
    </body>
</html>