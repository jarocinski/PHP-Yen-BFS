<?php
include "Yen-BFS.php";

 $persons= [ # sample names refer to the test.ged
     'I000001' => 'mja',
     'I002764' => 'Lusieńka',
     'I017036' => 'Wojciech Jarociński ojciec Małgorzaty',
     'I021954' => 'Teresa Sokołowska ż.Wojciecha J.',
     'I001455' => 'Antoni A. Szaszkiewicz skomplikowana rodzina',
     'I025995' => 'Janusz Jarociński z Warszawy',
     'I021940' => 'Tekla Szołowska',
     'I011721' => 'Frycek',
     'I011563' => 'Król Stanisław August Poniatowski'
 ];
$pid1 = isset($_POST['person1']) ? $_POST['person1'] : 'I017036';
$pid2 = isset($_POST['person2']) ? $_POST['person2'] : 'I021940';

$options1=$options2 = '';
foreach($persons as $personID => $personName):
    $options1 .= "<option value='$personID' " . ($personID==$pid1 ? " selected>" : ">") . "$personName </option>\n";
    $options2 .= "<option value='$personID' " . ($personID==$pid2 ? " selected>" : ">") . "$personName </option>\n";
endforeach;
$fromNode = "<select name='person1' form='nextpath' style='margin-bottom:10px;'>\n$options1</select>";
$toNode = "<select name='person2' form='nextpath'>$options2</select>";
?>

<head>
    <title>Search K shortest paths</title>
    <style>
        table {
            border: 1px solid black;
            background-color: #e8dccb;
            border-spacing: 4px;
        }
        .pers {
            min-width: 90px;
            max-width: 250px;
            border: 1px solid black;
            text-align: center;
            background-color: #dcc9ae;
        }
        input[type='checkbox'] {
            transform: scale(1.3);
            filter: grayscale(1);
        }
    </style>
</head>
<body>
<!--note: all values posted by the form are kept for the next run-->
<form method="POST" id="nextpath">
    <br><b>Searching paths</b>
    <label for="maxL">&nbsp;no longer than
        <input type="number" id="maxL" name="maxL" style="width:6ch;"
            value="<?php echo isset($_POST['maxL']) ? $_POST['maxL'] : "55" ?>" /></label>
    <label for="maxR">&nbsp;in search runs
        <input type="number" id="maxR" name="maxR" style="width:6ch;"
            value="<?php echo isset($_POST['maxR']) ? $_POST['maxR'] : "555" ?>" /></label>

    <label for="names">&nbsp;Show full names
        <input type="checkbox" id="names" name="names"
            <?php if (!isset($_POST["sel"]) && !isset($_POST["irn"])) echo "checked"; ?>
            <?php if(isset($_POST['names']) || isset($_POST['rels'])) echo "checked"; ?>
        /></label>
    <label for="rels">&nbsp;Display names in 2D diagram
        <input type="checkbox" id="rels" name="rels"
            <?php if (!isset($_POST["sel"]) && !isset($_POST["irn"])) echo "checked"; ?>
            <?php if(isset($_POST['rels'])) echo "checked"; ?>
        /></label>
    <?php
    echo "<br><pre>from $fromNode<br>  to $toNode</pre>"; # input selection of from-to names
    ?>
    <input type="submit" name="sel" value="Run the search for selected persons" />

    <p style="margin-top:15px;margin-bottom:8px;">
        ...or just enter IRN numbers of the two persons:</p>
    <label for="irn1"><code>person1 IRN:
        <input type="number" id="irn1" name="irn1" max="30000" style="width:8ch;"
            value="<?php echo isset($_POST['irn1']) ? $_POST['irn1'] : "1" ?>" /></code></label>
    <label for="irn2"><code>&nbsp;&nbsp;&nbsp;person2 IRN:
        <input type="number" id="irn2" name="irn2" max="30000" style="width:8ch;"
            value="<?php echo isset($_POST['irn2']) ? $_POST['irn2'] : "13160" ?>" /></code></label>
    <p style="margin-top:10px;">
        <input type="submit" name="irn" value="Run the search for submitted IRNs" />
    </p>
</form>
<hr>

<?php
if (!isset($_POST["sel"]) && !isset($_POST["irn"])): return; endif; # not called yet
//scrollTo(0,0);
# check parameters
if(isset($_POST['maxR']) && is_numeric($_POST['maxR'])): $maxRuns = $_POST['maxR'];
else: echo "\n<br>Bad maxRuns parameter"; return; endif;
if(isset($_POST['maxL']) && is_numeric($_POST['maxL'])): $maxLength = $_POST['maxL'];
else: echo "\n<br>Bad maxLength parameter"; return; endif;
if (isset($_POST["sel"])):
    $fromNode = $_POST['person1'];
    $toNode = $_POST['person2'];
elseif (isset($_POST["irn"])):
    if(isset($_POST['irn1']) && is_numeric($_POST['irn1'])): $fromNode = "I".sprintf("%06d",$_POST['irn1']);
    else: echo "\n<br>Bad person1 IRN parameter"; return; endif;
    if(isset($_POST['irn2']) && is_numeric($_POST['irn1'])): $toNode = "I".sprintf("%06d",$_POST['irn2']);
    else: echo "\n<br>Bad person1 IRN parameter"; return; endif;
    if (!isset($graph[$fromNode])): echo "Person IRN=$fromNode is not in Database!"; return; endif;
    if (!isset($graph[$toNode])): echo "Person IRN=$toNode is not in Database!"; return; endif;
endif;
if ($fromNode==$toNode): echo "The same person selected as start and end!"; return; endif;

echo "\n\n<br><br>Person1 $fromNode is&nbsp;<samp> $namesDict[$fromNode]</samp>";
echo "\n<br>Person2 $toNode is&nbsp;<samp> $namesDict[$toNode]</samp>";
$longestSoFar=0;
$relevant=0;
for ($k=1;$k<=$maxRuns;$k++):
YensNextPath($graph,$fromNode,$toNode,$kShortestPaths,$maxLength);
if (!$pathExists):
echo "\n<br>There is no connection between $fromNode and $toNode shorter than $maxLength";
return; # exit from Yen-BFS and return to index
endif;
$relStrings[$k] = constructRelString($kShortestPaths[$k],$graph);
$longestSoFar=max($longestSoFar,strlen($relStrings[$k]));
if (skipPath ($k,$relStrings,$kShortestPaths[$k],$graph,$echo) ):
continue; # go to finding the next path
else:  # display results
echo "\n\n<br><br> The $k"."th path is:\n<br>";
$relevant+=1;
if (!isset($_POST["names"])&&!isset($_POST["rels"])): # don't display names
echo implode('-',$kShortestPaths[$k]);
else: # display full names and relatioships
//                echo "<br>wyświetlanie nazwisk włączone<br>";
$relTable['M'] = ['p'=>'father','c'=>'son','s'=>'husband'];
$relTable['F'] = ['p'=>'mother', 'c'=>'daughter', 's'=>'wife'];
$pathNames=[]; $pathNames[] = $namesDict[$fromNode]; # creating aux list of fullnames
echo "\n<br>$namesDict[$fromNode]";
for ($ord=0;$ord<strlen($relStrings[$k]);$ord++):
$prevID = $kShortestPaths[$k][$ord];
$persID = $kShortestPaths[$k][$ord+1];
$fullName = $namesDict[$persID];
$pathNames[] = $fullName;
$gender = $sexDict[$persID];
$hisHer = $sexDict[$prevID]=='M'?'his':'her';
$rel = $relStrings[$k][$ord];
$relation = $relTable[$gender][$rel];
echo " --$hisHer $relation $fullName";
endfor;
endif;
echo "\n<br> rel.sentence = $relStrings[$k]";
$l=strlen($relStrings[$k]); $m=substr_count($relStrings[$k],'s');
echo " (length = $l incl.$m marriages)";

if (isset($_POST["rels"])): # prepare 2d diagram
$relStr = $relStrings[$k];
//                echo "\n<br>$relStr";
$xMax = substr_count($relStr,'s')+substr_count($relStr,'pc')+1;
$y=$yMax=$yMin=0;
for ($i=0;$i<strlen($relStr);$i++): # calc y range
if ($relStr[$i]=='p') $yMin = min(--$y,$yMin);
if ($relStr[$i]=='c') $yMax = max(++$y,$yMax);
endfor;
$yMax=$yMax-$yMin+1; # counting from 0 finally
$XY = array_fill(0,$xMax,array_fill(0,$yMax,' ')); # init empty array
$x=0; $y=-$yMin; #start position
$XY[$x][$y] = '(@) '.$pathNames[0]; # first person
foreach (range(0,strlen($relStr)-1) as $i):
$x+=$pcs2move[$relStr[$i]]['x'];
$y+=$pcs2move[$relStr[$i]]['y'];
if (substr($relStr,$i,2)=='pc'): $XY[$x][$y]='merge'; $x++; endif;
$XY[$x][$y]='('.$relStr[$i].') '.$pathNames[$i+1];
endforeach;

$XYtr = array_map(null, ...$XY); # transposing array - what a clever method!
$borders=['p'=>" border-bottom:none ",'c'=>" border-top:none ",'s'=>'border-left:none ','@'=>'border:double '];
# push php array to html
$colspan2="";
$out = "<table>";
    foreach ($XYtr as $row):
    $out .= "<tr>";
        foreach($row as $cell):
        if ($cell=='merge'): $colspan2="colspan='2'"; continue;
        elseif ($cell==' '): $out .= "<td class='empty'/>";
        else:
        //                            $border=$borders[$cell[0]];
        $out .= "<td class='pers' " . $colspan2 . ">$cell</td>"; $colspan2="";
        endif;
        endforeach;
        $out .= "</tr>";
    endforeach;
    $out .= "</table>";
echo $out;
endif; # end of displaying 2d
endif;
endfor;
echo "\n\n<br><br>No more paths shorter than $maxLength found in $maxRuns runs";
echo "<br>(the longest path checked was $longestSoFar)";
echo "<br>Found $relevant relevant paths";

