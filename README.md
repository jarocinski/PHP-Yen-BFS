# PHP-Yen-BFS
The PHP implementation of Yen's algorithm of finding K shortest paths in bidirectional loopless positive weighted graph.

The project is intended for genealogical graphs where edges represent "coitions" and "filiations" between persons (coition is relationship between spouses, filition is between child and each of its parents).

Yen's algorithm itself is implemented as a function which calculates the next shortest path at each call.
The shortest path function (required by Yen's) implements both-sides BFS algorithm.
Numerous "next shorthest paths" found by Yens can be irrelevant from genealogical viewpoint (e.g., there are similar paths going between siblings via their father as well as via mother); several such cases are filtered by skipSentence function.
The functions are included in four PHP modules:
- index.php contains a form setting parameters
- Yen-BFS.php contains the functions calculating the next path, checking relevance of solution, and some other auxiliaries
- BFS.php contains biBFS calculation with auxiliary constructPath and getFam functions (the latter is useful for access of the graph data via SQL)
- parseGED.php creates the graph from a GEDCOM file (graph is a list of nodes each containing list of its neighbours)
Each module contains a main part which is used only if the module is run directly and not included by the parent module.
