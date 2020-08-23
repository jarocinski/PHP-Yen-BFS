<?php
#  parseGedcom and some auxiliary functions

define('SPOUSE', 's');
define('PARENT', 'p');
define('CHILD', 'c');
define('SPOUSEDIST', 0.01);

function indi($lin) {  # wycina z linii 0 identyfikator pomiędzy znakami @
    $l = explode("@", $lin);
//    if ($l[2] != " INDI")
    return $l[1];
}

function fullName($lin) {  # wycina z linii 1 Imię i Nazwisko
//    if (preg_match("/^1 NAME /", $lin))
    return substr($lin,7);
}

function gender($lin) {  # wycina z linii 1 płeć
//    if (preg_match("/^1 SEX /", $lin))
    return $lin[6];
}

function printDots($licznik) {  # drukowanie kropek w miarę postępu
    # użycie: licznik = printDots(licznik)
    $licznik += 1;
    if ($licznik < 150) echo "."; if ($licznik == 150) echo "\n<br>";
    if ($licznik % 200 == 0) echo ".";
    if ($licznik % 40000 == 0) echo"\n<br>";
    return $licznik;
}

/*
function printLiveLine(licznik, value, lCh="=", maxVal=29000, $linLength=80)
{
    licznik += 1
    if licznik % 100 == 1:
        dotNum = int(value * $linLength / maxVal)
        print('\r' + lCh * dotNum + " " * ($linLength - dotNum), end = '', flush = True)
    return licznik
}
*/

# weight of a path where filiation is 1.00 and coition is 1.01
function weightof($path) {
    global $graph; # use the original graph (inside Yen's there is a local copy which is modified by the algorithm)
    $w=count($path)-1;
    for ($i = 1; $i<count($path); $i++):
        if ($graph[$path[$i-1]][$path[$i]] == SPOUSE): $w+=SPOUSEDIST; endif;
    endfor;
    return $w;
}

function parseGedcom($gedcomfile) {
/** @param $gedcomfile - full path to file - read the file and create:
 *  @return $People - the resulting graph i.e. array of all persons and their coitions and filiations
 *    $People[x] is array of neighbors, each neighbor is array ID=>[rel,dist,prev]
 *  @global $namesDict - array of full names (ID=>name)
 *  global $sexDict - array of genders (ID=>gender)
**/
    global $namesDict;
    global $sexDict;
    $namesDict=$sexDict = [];
    $file = fopen($gedcomfile,'r')
        or die("fail to open file");
    $licz = 0;  # tylko do kropek postępu
//    echo "Budowanie grafu\n<br>Ludzie ";
    # szukamy pierwszego INDI
    $curline = fgets($file);
    while (strncmp($curline,"0 @I",4)) $curline = fgets($file); # pomijamy aż do pierwszego INDI
    # znaleziony INDI, teraz zbieramy kolejnych aż trafi się FAM
//    if (feof($file)) return; # przedwczesny koniec pliku
    $People = [];
    while(strncmp($curline,"0 @F",4)): # obrabiamy kolejne linie aż natrafimy na FAM
//        $x=strncmp($curline,"0 @I",4);echo"  $x  ";
        if (strncmp($curline,"0 @I",4)==0): # kolejny INDI - wstawić do słownika nazwisk
            $People[indi($curline)] = [];  # dopisuje id z pustą tablicą krewnych
            $namesDict[indi($curline)] = fullName(fgets($file)); # w następnej linii musi być nazwisko
            $sexDict[indi($curline)] = gender(fgets($file)); # w następnej linii musi być sex
            $licz = printDots($licz);
        endif;
        $curline = fgets($file);
//    if (feof($file)) return; # przedwczesny koniec pliku
    endwhile;
    echo "\n<br>$licz persons found; now families";
    # znaleziony FAM, teraz dopisujemy osoby z rodziny
    $parents = [];  # lista rodziców w tej rodzinie - czasem tylko jeden
    $children = [];  # lista dzieci w tej rodzinie - czasem pusta
//    echo "\n<br>znaleziona rodzina $curline";
    while (!feof($file)): #teraz już do końca pliku zbieramy rodziny
        $licz = printDots($licz);
        $curline = fgets($file); # kolejna linia po FAM
//        echo "\n<br>członkowie rodziny: $curline";
        if (strncmp($curline,"1 HUSB", 6)==0):
            $parents[] = indi($curline);
        elseif (strncmp($curline,"1 WIFE", 6)==0):
            $parents[] = indi($curline);
        elseif (strncmp($curline,"1 CHIL", 6)==0):
            $children[] = indi($curline);
        else: # koniec osób w tej rodzinie - utworzyć krawędzie grafu dla wszystkich par w rodzinie
//            if($parents[0]=='I001258'){echo"parents: ".print_r($parents)." children: ".print_r($children);}
//        echo "parents <===>  children";
            if (sizeof($parents) == 2):  # są oboje rodzice - utworzyć 2x edge między nimi
                $People[$parents[0]][$parents[1]] = SPOUSE;
                $People[$parents[1]][$parents[0]] = SPOUSE;
            endif;
            foreach ($parents as $par):
                foreach ($children as $chi):  # dla każdego dziecka utworzyć 2x edge z rodzicami koszt=1
                    $People[$par][$chi] = CHILD;
                    $People[$chi][$par] = PARENT;
                endforeach;
            endforeach;
            # zbierając rodzine natrafiliśmy na jakąś inną linię - jeśli to nie FAM to pomijamy do FAM
            while (strncmp($curline,"0 @F",4)):
                $curline = fgets($file);
                if (feof($file)) break 2;
            endwhile; # mamy kolejny FAM
            if (strncmp($curline,"0 @F",4)==0):
//                echo "\n<br>znów rodzina";
                $parents = [];  # lista rodziców w tej rodzinie - czasem tylko jeden
                $children = [];  # lista dzieci w tej rodzinie - czasem pusta
                continue; # kolejna rodzina - kontynuujemy od początku while
            endif;
        endif; # koniec członków rodziny
    endwhile; # koniec while po rodzinach
    fclose($file);
    echo "\n<br>Total $licz records read<br>\n";
    return $People;
}

###########################################
$pathIn = "";
$gedIn = "test.ged";

$gedfile = $pathIn.$gedIn;

echo "Parsing $gedfile ";

$graph = parseGedcom($gedfile);
$numPeople = sizeof($graph);
//echo "Number of graph nodes (i.e., number of people in the tree): $numPeople<br>";
echo "<hr>";
//print_r($graph);
//print_r($namesDict);
