<?php
include "Yen-BFS.php";

 $persons=array( # sample names refer to the test4.ged
'I000001' => 'mja',
'I002764' => 'Lusieńka',
'I017036' => 'Wojciech Jarociński ojciec Małgorzaty',
'I001455' => 'Antoni A. Szaszkiewicz skomplikowana rodzina',
'I025995' => 'Janusz Jarociński z Warszawy',
'I021940' => 'Tekla Szołowska',
'I011721' => 'Frycek'
);
$pid1 = isset($_POST['person1']) ? $_POST['person1'] : 'I017036';
$pid2 = isset($_POST['person2']) ? $_POST['person2'] : 'I021940';

$options1=$options2 = '';
foreach($persons as $personID => $personName):
    $options1 .= "<option value='$personID' " . ($personID==$pid1 ? " selected>" : ">") . "$personName </option>\n";
#    $options1 .= "<option value='$personID'>$personName</option>\n";
    $options2 .= "<option value='$personID' " . ($personID==$pid2 ? " selected>" : ">") . "$personName </option>\n";
endforeach;
$fromNode = "<select name='person1' form='nextpath' style='margin-bottom:10px;'>\n$options1</select>";
$toNode = "<select name='person2' form='nextpath'>$options2</select>";
?>

<hr>
<form method="POST" id="nextpath">
    <br><b>Searching paths</b>
    <label for="maxL">&nbsp;no longer than
        <input type="number" id="maxL" name="maxLength" style="width:6ch;" value="25" /></label>
    <label for="maxR">&nbsp;in search runs
        <input type="number" id="maxR" name="maxRuns" style="width:6ch;" value="30" /></label>
    <label for="names">&nbsp; Show full names
        <input type="checkbox" id="names" name="names" style="transform:scale(1.3);" /></label>
    <label for="rels">&nbsp; with relationship
        <input type="checkbox" id="rels" name="rels" style="transform:scale(1.3);" /></label>
    <?php
    echo "<br><pre>from $fromNode<br>  to $toNode</pre>"; # input selection of from-to names
    ?>
    <input type="submit" name="sel" value="Run the search for selected persons" />

    <p style="margin-top:15px;margin-bottom:8px;">
        ...or just enter IRN numbers of the two persons:</p>
    <label for="irn1"><code>person1 IRN:
        <input type="number" id="irn1" name="irn1" max="30000" style="width:8ch;" value="1" /></code></label>
    <label for="irn2"><code>&nbsp;&nbsp;&nbsp;person2 IRN:
        <input type="number" id="irn2" name="irn2" max="30000" style="width:8ch;" value="13160" /></code></label>
    <p style="margin-top:10px;">
        <input type="submit" name="irn" value="Run the search for submitted IRNs" />
    </p>
</form>
