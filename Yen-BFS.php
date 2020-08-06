<?php
include('parseGED.php');
include ('BFS.php');

if (!$_POST):  # tzn wywolanie bez danych z formularza
    return;
endif;


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
 * @param &$kShortestPaths - table (reference) of shortest paths found so far
 * @param integer $lengthLimit - jak daleko szukać
 **/
function YensNextPath($edges,$start,$finish,&$kShortestPaths,$lengthLimit) {
    static $k = 0; # path number (first found will be 1 etc.)
    static $candidatePaths = []; # candidates (remembered also from earlier calls)
    static $candidateWeights = []; # candidate weights stored in parallel to candidates
    if ($k == 0): # Just find the shortest path
        $k = 1;
        if (mjaBFS($edges, $start, $finish, $shortest)):
            $kShortestPaths[$k] = $shortest;
//            echo "\n<br>== The shortest path from $start to $finish is:\n<br>".implode('-',$shortest);
//            printFilCoi(weightof($shortest));
        else:
            echo "There is no connection between $start and $finish";
        endif;
        return;
    endif;
    # Searching for k+1 path while previous k are already stored in $kShortestPaths.
    # kth path nodes are taken one by one as $spurNode; preceding part is $rootPath
    # nodes of $rootPath are made unaccessible as well as links of $spurNode previously used
    $kthPath = $kShortestPaths[$k];
    $kthLength = count($kthPath);  # number of nodes, not weight
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
                echo " found path is longer than limit $lengthLimit";
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
                    break;  # braking "for" - taking next $spurNode
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

function constructSentence(array $path, array $graph): string {
    $sentence = '';
    for ($person=1; $person<sizeof($path); $person++):
        $sentence .= $graph[$path[$person-1]][$path[$person]];
    endfor;
    return $sentence;
}

# skip the path if it is negligible in the genealogy sense
function skipSentence (int $k, array $allSentences, array $kthPath, array $graph, bool $echo): bool {
    $skip = FALSE; # mark if the path is useful
    if (array_search($allSentences[$k], $allSentences) < $k): # duplicate s in relStrings
        $skip = TRUE;
        $dupl = array_search($allSentences[$k], $allSentences);
        if ($echo): echo "(note that $k th = $allSentences[$k] is parents-equivalent to $dupl th)\n<br>"; endif;
    elseif (substr_count($allSentences[$k], 'cp') > 0):
        $skip = TRUE;
        if ($echo): echo "(note that $k th = $allSentences[$k] goes between parents via a child and not directly)\n<br>"; endif;
    elseif (substr_count($allSentences[$k], 'ps') > 0): # check if 'ps' concerns both parents (and not other marriage of a parent)
        $from = 0; # start search from beginning, then after an occurence
        while (($pos = strpos($allSentences[$k], 'ps', $from)) !== FALSE):
            if (isset($graph[$kthPath[$pos]][$kthPath[$pos + 2]])):
                $skip = TRUE;
                if ($echo): echo "(note that $k th = $allSentences[$k] goes to a parent via the other parent and not directly)\n<br>"; endif;
            endif;
            $from = $pos + 1;
        endwhile;
    elseif (substr_count($allSentences[$k], 'sc') > 0): # check if 'sc' concerns child of both parents (and not other marriage of a parent)
        $from = 0; # start search from beginning, then after an occurence
        while (($pos = strpos($allSentences[$k], 'sc', $from)) !== FALSE):
            if (isset($graph[$kthPath[$pos]][$kthPath[$pos + 2]])):
                $skip = TRUE;
                if ($echo): echo "(note that $k th = $allSentences[$k] goes to a child via the other parent and not directly)\n<br>"; endif;
            endif;
            $from = $pos + 1;
        endwhile;
    endif;
    return $skip;
}

##########################################################

$maxK = 18; # how many times to call for next path
$maxLength = 20;  # max liczba łączy w ścieżce (nie waga); max liczba _par_ kroków przy biBFS jest $maxLength/2
//$num = ['','shortest','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];
//$num=array_merge($num,array_fill(0,$maxK-10,'nTh'));
$echo=FALSE;

$kShortestPaths = []; # next paths will be saved here
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
        $relStrings[$k] = constructSentence($kShortestPaths[$k],$graph);
        if (skipSentence ($k,$relStrings,$kShortestPaths[$k],$graph,$echo)):
            continue; # go to next path
        else:
            echo "\n\n<br><br> The $k th path is:\n<br>";
            echo implode('-',$kShortestPaths[$k]);
            echo "\n<br> rel.sentence = $relStrings[$k]";
            $l=strlen($relStrings[$k]); $m=substr_count($relStrings[$k],'s');
            echo " (length = $l incl.$m marriages)";
        endif;
    endfor;

else:
//    echo '<br><br>*********** wywołanie  z index.php: ';
//    if (isset($_POST['searchnext'])):
    echo print_r($_POST);
    if(isset($_POST['maxRuns']) && is_numeric($_POST['maxRuns'])):
        $maxRuns = $_POST['maxRuns'];
    else: echo "\n<br>Bad maxRuns parameter"; return;
    endif;
    if(isset($_POST['maxLength']) && is_numeric($_POST['maxLength'])):
        $maxLength = $_POST['maxLength'];
    else: echo "\n<br>Bad maxLength parameter"; return;
    endif;
    $fromNode = $_POST['person1'];
    $toNode = $_POST['person2'];
//    echo "from = $fromNode, to = $toNode \n<br>";
    for ($k=1;$k<=$maxRuns;$k++):
        YensNextPath($graph,$fromNode,$toNode,$kShortestPaths,$maxLength);
        $relStrings[$k] = constructSentence($kShortestPaths[$k],$graph);
        if (skipSentence ($k,$relStrings,$kShortestPaths[$k],$graph,$echo)):
            continue; # go to next path
        else:
            echo "\n\n<br><br> The $k"."th path is:\n<br>";
            echo implode('-',$kShortestPaths[$k]);
            echo "\n<br> rel.sentence = $relStrings[$k]";
            $l=strlen($relStrings[$k]); $m=substr_count($relStrings[$k],'s');
            echo " (length = $l incl.$m marriages)";
        endif;
    endfor;
    echo "\n\n<br><br>No more paths shorter than $maxLength found in $maxRuns runs";

endif;


##completed: pc: do rodzeństwa przez drugiego wspólnego rodzica (sentencja ident.z którąś wczesniejszą) --DONE
##completed: ps: od dziecka do rodzica przez drugiego rodzica (o 1 dłuższa; sprawdzać czy nie przyrodni) --DONE
##completed: sc: ścieżka przez drugiego rodzica do dziecka (o 1 dłuższa; sprawdzac czy nie przyrodni) --DONE
##completed: cp: do drugiego rodzica przez ich dziecko (o 1 dłuższa) --DONE
