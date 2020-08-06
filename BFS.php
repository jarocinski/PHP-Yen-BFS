<?php
//include('parseGED.php');

$inv = [SPOUSE=>SPOUSE, PARENT=>CHILD, CHILD=>PARENT]; # to invert relationship when reading path backwards

/* For the graph $edges function getFam returns all neighbour nodes of every nodes listed in $personList.
 * If the neighbour was already visited in earlier step going from the same side it is ignored.
 * If it was visited from oposite side - this means that it is in the middle of the searched path
 * and the string containing appropriate info is returned. If no new neighbours are found the $step is returned.
 * The function is separated from YensNextPath for easier usage in case the graph is retrieved from database.
 * @param array $edges - graph in the form of list of neighbours of every node (array of arrays all indexed by ID)
 * @param array $personList - list of nodes the neighbours are to be found
 * @param integer $step - searching step number (either positive or negative)
 * @global $visited - list of visited nodes (distance, prev node, relation to the prev)
 */
function getFam ($edges, $personList, $step) {
    # get all parents, children and spouses of every people in $personList
    global $visited; # will be used to retrieve the path -- visited[nid]=(dist,vfrom,rel)
    $newNeighbours = [];
    foreach ($personList as $person):
        if (!isset($edges[$person])){echo"\n<br>error: ";print_r($edges);exit('gkjhgkjhgkjgh');}
        foreach ($edges[$person] as $nearID=>$rel): # neighbours of $person; finally it is supposed to use SELECT
            if (!isset($nearID)): echo "\n<br> ---- $person has no neighbours"; continue; endif;
            if (!isset($visited[$nearID])): # not visited yet - add it to $visited
                $visited[$nearID] = ['dist'=>$step, 'vfrom'=>$person, 'rel'=>$rel];
                $newNeighbours[] = $nearID;  # here is the list of new neighbours
//                echo "\n<br>dopisujemy do nowoodwiedzonych ".$edges[$person][$key]['nid'];
            elseif ($visited[$nearID]['dist'] * $step < 0): # found!
//                echo "\n<br>w kroku $step spotkany $nearID wcześniej odwiedzony w kroku ".$visited[$nearID]['dist'];
                return $nearID.','.strval($step).','.$person.','.$rel;
            endif;
        endforeach;
//        echo "\n<br>nie ma wiecej bliskich osoby $person";
    endforeach;
//    echo "\n<br> wszyscy ".sizeof($newNeighbours)." znalezieni w kroku $step: ";
//    echo implode(' ', $newNeighbours) ."--<br>\n";
    return count($newNeighbours)==0 ? $step : $newNeighbours;
} # end of getFam definition

/** biBFS consists of two separate searches from source and from destination.
 * Both searches are recorded by the same array $visited (indexed by id) with
 * source starting at 1 and increasing and destination at -1 and decreasing. Each step of the search
 * adds the new people visited on that step (either + or -). Finding an entry of the
 * opposite sign means the search is concluded whereas if it is of the same sign it is discarded.
 * If no new people are found on either direction of the searches, it fails immediately, as an isolated
 * part of the tree graph has been detected.
 * @param array $edges - graph is the list of neighbour IDs of every node
 * @global $visited - list of visited nodes (distance, prev node, relationsip of the prev)
 * @param string $start - start node ID
 * @param string $target - target node ID
 * @param int $stepLimit - max. liczba kroków badania sąsiadów (z obu stron)
 * @return mixed $found: string (ID etc of the node visited from both sides)
 *   or int (no neighbour found in step $step) or int ($stepLimit reached)
 **/
function biBFS ($edges, $start, $target, $stepLimit) {
    global $visited; # $visited is gradually filled by getFam with neighbours of previously visited nodes
    # graf drzewa jest wykorzystywany tylko pośrednio poprzez getFam()
//    echo "\n<br>biBFS: start $start, target $target";
    if ($start==$target): return $start.",0,".$target; endif;
    $visited = []; $visited[$start]['dist']=1; $visited[$target]['dist']=-1;
    $forthback[1] = [$start]; $forthback[-1] = [$target]; # recently visited forth and back (starting from $start and $target)
    $step=1;
    while (TRUE): # collect visited nodes until a visited from oposite side is found or no unvisited can be found
        $step++; if ($step>$stepLimit) return $stepLimit; # poznamy to po typie int
        foreach ([1,-1] as $direction):
            $result = getFam($edges, $forthback[$direction],$direction*$step); # sąsiedzi wszystkich ostatnio odwiedzonych w przód/tył
            if (is_array($result)): # new neighbours found
                $forthback[$direction] = $result;
//                continue; # after forth do back in the foreach loop; after both are done continue while-true loop
            elseif (is_string($result)):
                return $result; # found from both sides (info is composed to string)
            elseif (is_int($result)): # could be just "else"
//                echo "\n<br+++++++++brak sąsiadów $start w kroku $step --".print_r($result);
                return $step*$direction; # no neighbours - isolated graph nodes
            endif;
        endforeach; # the pair of steps was done (forth and back) - continue while-true
    endwhile;
//    return ''; # not needed (never reached) but postulated by IDE
} # end of biBFS definition

/* Reconstruct the path with the use of info saved in $found and $visited.
 * Half of relationships are recorded in reverse so have to be inverted */
function constructPath ($start,$target,$found) {
    global $visited;
    $expl = explode(',',$found);
    $step = $expl[1];
//    echo "\n<br>znaleziony ".explode(',',$found)[0]." step znalezionego = $step";
    $x = $step>0 ? 0 : 2; # start reconstruction forth or back
    $startBack  = $expl[$x];
    $startForth = $expl[2-$x];
    # if $step is positive go towards $target, if negative - towards $start
    $indi = $startBack;
    $path[0] = $indi;
    while ($indi!=$target): # końcowa połówka
        $indi = $visited[$indi]['vfrom'];
        $path[] = $indi;
    endwhile;
    $indi = $startForth;
    array_unshift($path,$indi);
    while ($indi!=$start): # początkowa połówka
        $indi = $visited[$indi]['vfrom'];
        array_unshift($path,$indi);
    endwhile;
//    echo "\n<br>odtworzona ścieżka: ".implode(' ',$path);
    return $path;
} # end of constructPath definition

##############################
//global $visited;
if (count(get_included_files())==2) { # tzn że uruchamiamy ten skrypt z ręki a nie jako include
    echo "\n<br>-------------test biBFS--------------";
    $maxLength = 20;
    $pers1 = "I000001";
    $pers2 = "I021413";
    $bfsFound = biBFS($graph, $pers1, $pers2, $maxLength/2);
    if (is_string($bfsFound)):
        echo "\n<br>=-=-=-=znaleziono = $bfsFound =-=-=-=<br\n";
        $pathCompleted = constructPath($pers1, $pers2, $bfsFound);
        echo "\n<br>cała ścieżka: " . implode(' ', $pathCompleted);
//    echo "\n<br>pokrewieństwo: $sentence";
    elseif (is_int($bfsFound)):
        if ($bfsFound==$maxLength/2): echo "\n<br>No path between $pers1 and $pers2 shorter than $maxLength/2 steps";
        else: echo "\n<br>Isolated $pers1 and $pers2 - no neighbours found after $bfsFound steps";
        endif;
    endif;
//echo "\n<br>vvvvisited: "; print_r($visited);
}
