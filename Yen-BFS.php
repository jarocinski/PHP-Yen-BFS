<?php
include('parseGED.php');
include ('BFS.php');
/** created in parseGED.php:
 * @var array $graph
 * @var array $namesDict
 */
if (!$_POST):  # called without form data
    return;
endif;
$pathExists = FALSE;
# translate relationships to xy change in 2d diagram
$pcs2move=['p'=>['x'=>0,'y'=>-1],'c'=>['x'=>0,'y'=>1],'s'=>['x'=>1,'y'=>0]];

function mjaBFS($edges, $start, $finish, &$shortestpathfound) {
    global $maxLength;
//    echo "\n<br>edges[I000001] w mjaBFS = "; print_r($edges['I000001']);
    if ($start==$finish) exit("\n<br>The same person selected as start and finish!");
    $spotkanie = biBFS($edges, $start, $finish, $maxLength/2);
    if (is_string($spotkanie)):
        $shortestpathfound = constructPath($start,$finish,$spotkanie);
        return TRUE;
    elseif (is_int($spotkanie)):
//        if ($spotkanie==0): echo "\n<br>brak (obecnie) połączenia $start z $finish";
//        else: echo "\n<br>==$spotkanie== nie znaleziono połączenia krótszego od limitu $maxLength/2";
//        endif;
        return FALSE;
    endif;
    return NULL; # not needed! (just avoiding IDE notice)
}

/** In every call a next-shortest path is calculated. The Yen's algorithm calculates candidate paths
 *  by disabling consecutive graph nodes and outgoing edges that were used in the previous shortest
 *  path and the earlier candidates. Shortest of the candidates is taken as the nest shortest path.
 * @param array $edges - the graph
 * @param string $start - ID of start person (node)
 * @param string $finish - ID of end person (node)
 * @param &$kShortestPaths - table (reference!) of shortest paths found so far
 * @param int $lengthLimit - max path length allowed
 **/
function YensNextPath($edges,$start,$finish,&$kShortestPaths,$lengthLimit) {
    static $k = 0; # path number (first found will be 1 etc.)
    static $candidatePaths = []; # candidates (remembered also from earlier calls)
    static $candidateWeights = []; # candidate weights stored in parallel to candidates
    global $pathExists;
    if ($k == 0): # just find the shortest path
        $k = 1;
        if (mjaBFS($edges, $start, $finish, $shortest)):
            $kShortestPaths[$k] = $shortest;
//            echo "\n<br>== The shortest path from $start to $finish is:\n<br>".implode('-',$shortest);
//            printFilCoi(weightof($shortest));
            $pathExists = TRUE;
        else:
            $pathExists = FALSE;
//            echo "\n<br>There is no connection between $start and $finish";
        endif;
        return;
    endif;
    # Searching for k+1 path while previous k are already stored in $kShortestPaths.
    # kth path nodes are taken one by one as $spurNode; preceding part is $rootPath
    # nodes of $rootPath are made unaccessible as well as links of $spurNode previously used
    $kthPath = $kShortestPaths[$k];
    $kthLength = count($kthPath);  # number of nodes, and not weight
    # main loop through nodes of kth path
    for ($spurNum = 1; $spurNum < $kthLength; $spurNum++):
        $rootPath = array_slice($kthPath, 0, $spurNum); # initial $spurNum nodes
        $spurNode = $kthPath[$spurNum-1];
//        echo "\n<br>spurNode$spurNum = $spurNode";
//        echo " rootPath: " . implode('r',$rootPath) . "<br>";
        # eliminate links of $spurNode that were used in any of the shortest paths beginning identical as the $rootPath
        foreach ($kShortestPaths as $testPath):
            if (array_slice($testPath, 0, $spurNum) == $rootPath):  # początki się pokrywają
                $spurLinkEnd = $testPath[$spurNum];
//                echo "\n<br> --usuwamy link między $spurNode a $spurLinkEnd \n<br>";
//                echo "edges[spurnode] przed usunięciem = "; print_r($edges[$spurNode]);
                unset($edges[$spurNode][$spurLinkEnd]); # eliminate link incoming
                unset($edges[$spurLinkEnd][$spurNode]); # ...and outgoing
//                echo "a po usunięciu = "; print_r($edges[$spurNode]);
                if (isset($edges[$spurNode]) && count($edges[$spurNode])==0) unset($edges[$spurNode]); # eliminate orphaned node
                if (isset($edges[$spurLinkEnd]) && count($edges[$spurLinkEnd])==0) unset($edges[$spurLinkEnd]);
            endif;
        endforeach;
        # eliminate $rootPath node preceding $spurNode (previous nodes were eliminated earlier)
        if (sizeof($rootPath)>1):
            $abrNode = $rootPath[$spurNum-2];  # numbering starts from 0
//            echo "<br>...i eliminujemy poprzedni węzeł tzn. $abrNode : ";
            if (isset($edges[$abrNode])):
                foreach ($edges[$abrNode] as $neighNode=>$rel):
    //                echo "\n<br>-przerywamy linki od sąsiadów: $neighNode do $abrNode";
    //                unset($edges[$abrNode][$neighNode]); # not necessary - $abrNode will be unset
                    unset($edges[$neighNode][$abrNode]);
                    if (count($edges[$neighNode])==0): unset($edges[$neighNode]); endif;
                endforeach;
    //            echo " i usuwamy $abrNode z grafu";
                unset($edges[$abrNode]);
    //            echo "\n<br>teraz graf: "; print_r($edges);
            endif;
        endif;
        # now find the shortest path from $spurNode to $finish in the reduced graph (if both still exist)
        if (isset($edges[$spurNode]) && isset($edges[$finish])
            && mjaBFS($edges, $spurNode, $finish, $shortest)):
            $spurPath = $shortest;
//            echo "\n<br>####spurPath " . implode('u',$spurPath);
            # merge to the $rootPath and update $candidatePaths
            $newcandPath = array_merge($rootPath, array_slice($spurPath, 1));
//            echo "\n<br>####newcandPath " . implode('c',$newcandPath);
            if (count($newcandPath)>$lengthLimit):
                echo "\n<br>found candidate path longer than limit $lengthLimit (however, it may turn out to be negligible)";
                break;
            else:  # check for duplicate
                $duplicate = FALSE;
                foreach ($candidatePaths as $checkPath):
                    if ($newcandPath == $checkPath):
                        $duplicate = TRUE;
                        break;
                    endif;
                endforeach;
                if ($duplicate):
//                    echo " ---pomijamy bo jest już wśród kandydatów";
                    break;  # breaking "for" loop - taking the next $spurNode
                endif;
                $candidateWeights[] = weightof($newcandPath); # parallel array containing weights
                $candidatePaths[] = $newcandPath;
//                echo "\n<br>wszyscy kandydaci dotąd: "; foreach($candidatePaths as $cnd) {echo "  ".implode('-',$cnd)."<br>\n";}
            endif;
//        else:
//            echo "\n<br>nie ma nowej ścieżki od spurNode $spurNode do $finish";
        endif;  # shortest from $spurNode found and candidate created (either successfuly or not))
    endfor;  # end of main loop through nodes of the kth (i.e., previous) shortest path
    if (count($candidatePaths)>0):  # take shortest candidate as the new shortest path
//        echo "\n<br>wszyscy kandydaci: \n<br>"; print_r($candidatePaths);
//        echo "\n<br>ich dlugosci \n<br>"; print_r($candidateWeights);
        $bestCandInd = array_search(min($candidateWeights),$candidateWeights);
        $bestCandidate = $candidatePaths[$bestCandInd];
//        echo "\n<br>najlepszy kandydat: index $bestCandInd \n<br>".implode('-',$bestCandidate);
//        $k+=1;
        $kShortestPaths[++$k] = $bestCandidate;
//        echo "\n<br>=== The $k th shortest path is:\n<br>" . implode('-',$kShortestPaths[$k]);
//        printFilCoi($candidateWeights[$bestCandInd]);
        unset($candidatePaths[$bestCandInd]); unset($candidateWeights[$bestCandInd]);
    else: echo "<br>==== there are no more shortest paths ";
    endif;
//    echo "\n\n<br><br>wszystkie najkrótsze dotąd: ";
//    foreach ($kShortestPaths as $testPath):
//        $we=weightof($testPath); echo "\n<br>".implode('-',$testPath)."($we)";
//    endforeach;
}  # end of YensNextPath

//function printFilCoi($distance) {
//    $filiations=intval($distance); $coitions=round(($distance-$filiations)*100);
//    echo " (distance is $filiations incl.$coitions marriages)";
//}

function constructRelString(array $path, array $graph): string {
    $sentence=$gender = '';
    for ($person=1; $person<sizeof($path); $person++):
        $sentence .= $graph[$path[$person-1]][$path[$person]];
        $gender .= $graph[$path[$person-1]][$path[$person]];
    endfor;
    return $sentence;
}

# skip the path if it is negligible in the genealogy sense
function skipPath (int $k, array $allRelStrings, array $kthPath, array $graph, bool $echo): bool {
    $kthRelString = $allRelStrings[$k];
    if (array_search($kthRelString, $allRelStrings) < $k): # duplicate s in relStrings
        $dupl = array_search($kthRelString, $allRelStrings); # position of duplicate
        if ($echo) echo "(note that $k th = $kthRelString is parents-equivalent to $dupl th)\n<br>";
        return true;
    endif;
    if (substr_count($kthRelString,'cp') > 0):
        if ($echo) echo "(note that $k th = $kthRelString goes between parents via a child and not directly)\n<br>";
        return true;
    endif;
    if (substr_count($kthRelString,'ps') > 0): # check if 'ps' concerns both parents (and not another marriage of a parent)
        $from = 1; # start search from beginning (excl.x), then after an occurence
        while ($pos=strpos('x'.$kthRelString, 'ps', $from)): # in the original string $pos may be 0 (equal to false!)
            if (isset($graph[$kthPath[$pos-1]][$kthPath[$pos+1]])):
                if ($echo) echo "(note that $k th = $kthRelString goes to a parent via the other parent and not directly)\n<br>";
                return true;
            endif;
            $from = $pos + 1;
        endwhile;
    endif;
    if (substr_count($kthRelString,'sc') > 0): # check if 'sc' concerns child of both parents (and not other marriage of a parent)
        $from = 1;
        while ($pos=strpos('x'.$kthRelString, 'sc', $from)):
            if (isset($graph[$kthPath[$pos-1]][$kthPath[$pos+1]])):
                return true;
            endif;
            $from = $pos + 1;
        endwhile;
    endif;
    return false;
}

##########################################################

$maxK = 30; # how many times to call for next paths
$maxLength = 20;  # max liczba łączy w ścieżce (nie waga); max liczba _par_ kroków przy biBFS jest $maxLength/2
//$num = ['','shortest','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];
//$num=array_merge($num,array_fill(0,$maxK-10,'nTh'));
$echo=false;

$kShortestPaths = []; # all paths will be saved here
$relStrings = []; # ...and relationship sequences here

if (count(get_included_files())==0): # jesli 3 to uruchamiamy ten skrypt z ręki a nie jako include
    echo "<br><br>*********** wywołanie bezpośrednie";
    //$fromNode = 'I000001';
    $fromNode = 'I017036'; # Wojciech Jarociński ojciec Małgorzaty
    //$toNode = 'I001455'; # Antoni A. Szaszkiewicz "skomplikowana rodzina"
    //$toNode = 'I025995'; # Janusz Jarociński
    $toNode = 'I021940'; #Tekla Szołowska
    //$num = ['','shortest','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];
    //$num=array_merge($num,array_fill(0,$maxK-10,'nTh'));

    for ($k=1;$k<=$maxK;$k++):
        YensNextPath($graph,$fromNode,$toNode,$kShortestPaths,$maxLength);
        if (!$pathExists) break;
        $relStrings[$k] = constructRelString($kShortestPaths[$k],$graph);
        if (skipPath ($k,$relStrings,$kShortestPaths[$k],$graph,$echo)):
            continue; # go to next path
        else:
            echo "\n\n<br><br> The $k th path is:\n<br>";
            echo implode('-',$kShortestPaths[$k]);
            echo "\n<br> rel.sentence = $relStrings[$k]";
            $l=strlen($relStrings[$k]); $m=substr_count($relStrings[$k],'s');
            echo " (length = $l incl.$m marriages)";
        endif;
    endfor;
else: # called from external script
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
        if ($fromNode==$toNode): echo "The same person selected as start and end!"; return; endif;
        if (!isset($graph[$fromNode])): echo "Person IRN=$fromNode is not in Database!"; return; endif;
        if (!isset($graph[$toNode])): echo "Person IRN=$toNode is not in Database!"; return; endif;
    endif; # parameters verified

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
                            $out .= "<td class='name' " . $colspan2 . ">$cell</td>"; $colspan2="";
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

endif;
