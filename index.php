<?php
include "Yen-BFS.php";

 $persons=array(
'I000001' => 'mja',
'I017036' => 'Wojciech Jarociński ojciec Małgorzaty',
'I001455' => 'Antoni A. Szaszkiewicz skomplikowana rodzina',
'I025995' => 'Janusz Jarociński z Warszawy',
'I021940' => 'Tekla Szołowska'
);
$pid1 = isset($_POST['person1']) ? $_POST['person1'] : 'I017036';
$pid2 = isset($_POST['person2']) ? $_POST['person2'] : 'I021940';

$options1=$options2 = '';
foreach($persons as $personID => $personName):
    $options1 .= "<option value='$personID' " . ($personID==$pid1 ? " selected>" : ">") . "$personName </option>\n";
#    $options1 .= "<option value='$personID'>$personName</option>\n";
    $options2 .= "<option value='$personID' " . ($personID==$pid2 ? " selected>" : ">") . "$personName </option>\n";
endforeach;
$fromNode = "<select name='person1' form='nextpath' style='margin-bottom:10px;'>\n$options1\n</select>";
$toNode = "<select name='person2' form='nextpath' style='margin-bottom:10px;'>\n$options2\n</select>";
echo "\n<br><br><b>Searching paths</b><br>";
echo "\n\n<br>from $fromNode";
echo "\n\n<br>&nbsp;&nbsp;...to $toNode";
?>

<form method="POST" id="nextpath">
    <input type="hidden" name="searchnext" value="1" />
    <br>
    <button type="submit">Search (the next) shortest</button>
    &nbsp; defaults:
    <label for="maxL">max path length</label>
    <input type="number" id="maxL" name="maxLength" style="width:6ch;" value="18" />
    <label for="maxR">max search runs</label>
    <input type="number" id="maxR" name="maxRuns" style="width:6ch;" value="20" />
</form>
