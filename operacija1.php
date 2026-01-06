<?php
// operacija1.php
// skirta pakeisti savo sudaryta operacija pratybose
session_start();
?>

<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=9; text/html; charset=utf-8">
        <title>Operacija 1</title>
    </head>
    <body>
    <table style="border-width: 2px; border-style: dotted;"><tr><td>
         Atgal į [<a href="index.php">Pradžia</a>]
      </td></tr>
	</table>
			
		<div style="text-align: center;color:green">
            <h1>Operacija 1.</h1>
		</div><hr>
		<div style="text-align: left">
			Čia gali būti jūsų operacija pabandymui.<br> 
			Nukopijuokite katalogą <span style="color:green">
				/home/stud/pratybos/demo/vartvald  į  /var/www/html
			</span><br>
			Savo programą įkelkite į <span style="color:green">
				/var/www/html/vartvald/
			</span><br>
			Paredaguokite <span style="color:green">
				/var/www/html/vartvald/include/nustatymai.php 
			</span>eilutę:<br>
			<p style="color:green">	
				$usermenu=array(["<span style="color:red">
					Demo operacija-1</span>
				",[0,4,5,9],<span style="color:red">
					"operacija1.php"</span>]
			</p>	
			
			Į meniu iš programos grįžtama html eilute:<br>
			<?php highlight_string('Atgal į [<a href="index.php">Pradžia</a>]');?>
			<br>
			Testuokite naršykle <span style="color:green">
				http://localhost/vartvald
			</span>
		</div>	
	</body>
</html>

	
