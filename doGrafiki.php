<head>
    <title>path placed in table</title>
    <style>
        table {
            border: 1px solid black;
            background-color: linen;
        }
        td.name {
            min-width:90px;
            border: 1px solid black;
            text-align: center;
            background-color: lightyellow;
        }
    </style>
</head>
<body>

<?php
# translate relationships to xy change
$pcs2move=['p'=>['x'=>0,'y'=>-1],'c'=>['x'=>0,'y'=>1],'s'=>['x'=>1,'y'=>0]];
$borders=['p'=>'border-bottom-style:hidden','c'=>'border-top-style:hidden','s'=>'border-left-style:hidden','@'=>'border-style:double'];


$relStr='cspppcsppspspccccccssc';
$pathNames = ['Michał /JAROCINSKI','Tekla /SZOLOWSKA/','Józef /SZASZKIEWICZ/','Lusia /WARDZYNSKA/','Stefan /JAROCINSKI/',
    'Stanisław August II /PONIATOWSKI/ król h.Ciołek','Cecylia Izabella /RYX/ h.Pierścień','Michał Hieronim /NAWROCKI/',
    'Krystian /BRODACKI/','Helena Wanda /MAŁUTOWSKA/',' Cecylia Antonina Magdalena /TRZCIŃSKA/ h.Pobóg',
    'Franciszka /KLIMKIEWICZ/','Barlaama /KOSSOBUDZKA','Maria /JAXA-ROŻEN/','Adolf /SZASZKIEWICZ/ h.wł.',
    'Michał /JAROCINSKI','Tekla /SZOLOWSKA/','Józef /SZASZKIEWICZ/','Lusia /WARDZYNSKA/','Stefan /JAROCINSKI/',
    'Stanisław August II /PONIATOWSKI/ król h.Ciołek','Cecylia Izabella /RYX/ h.Pierścień','Michał Hieronim /NAWROCKI/',
    'Krystian /BRODACKI/','Helena Wanda /MAŁUTOWSKA/',' Cecylia Antonina Magdalena /TRZCIŃSKA/ h.Pobóg',
    'Franciszka /KLIMKIEWICZ/','Barlaama /KOSSOBUDZKA','Maria /JAXA-ROŻEN/','Adolf /SZASZKIEWICZ/ h.wł.'];

echo "\n<br>$relStr";
$xMax = substr_count($relStr,'s')+substr_count($relStr,'pc')+1;
$y=$yMax=$yMin=0;
for ($i=0;$i<strlen($relStr);$i++): # calc y range
    if ($relStr[$i]=='p') $yMin = min(--$y,$yMin);
    if ($relStr[$i]=='c') $yMax = max(++$y,$yMax);
endfor;
$yMax=$yMax-$yMin+1; # counting from 0 finally
$XY = array_fill(0,$xMax,array_fill(0,$yMax,' ')); # init empty array
$x=0; $y=-$yMin; #start position
$XY[$x][$y] = '@'.$pathNames[0]; # first person
foreach (range(0,strlen($relStr)-1) as $i):
    $x+=$pcs2move[$relStr[$i]]['x'];
    $y+=$pcs2move[$relStr[$i]]['y'];
    if (substr($relStr,$i,2)=='pc'): $XY[$x][$y]='merge'; $x++; endif;
    $XY[$x][$y]=$relStr[$i].' '.$pathNames[$i+1];
endforeach;
$XYtr = array_map(null, ...$XY); # transposing array - what a clever method!
# push php array to html
$colspan2="";
$out = "<table>";
foreach ($XYtr as $row):
    $out .= "<tr>";
    foreach($row as $cell):
        if ($cell=='merge'): $colspan2="colspan='2'"; continue;
        elseif ($cell==' '): $out .= "<td class='empty'/>";
        else:
            $border=$borders[$cell[0]];
            $out .= "<td class='name' " . $colspan2 . ">$cell</td>"; $colspan2="";
        endif;
    endforeach;
    $out .= "</tr>";
endforeach;
$out .= "</table>";
echo $out;
