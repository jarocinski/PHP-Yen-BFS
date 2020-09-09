### PHP-Yen-BFS
PHP implementation of Yen's K-shortest paths algorithm.
The implemented algorithm computes K shortest loopless paths between a pair of nodes in a bidirectional unweighted graph.

The project is intended for genealogical graphs where edges represent "coitions" and "filiations" between persons (coition is relationship between spouses, filiation is between a child and each of its parents).

Yen's algorithm itself is implemented as a function which calculates one (the next shortest) path at each call.
The shortest path function (required by Yen's) implements both-sides BFS algorithm.

Numerous (most of the) "next shortest paths" found by the algorithm may be negligible from the genealogical viewpoint (e.g., there are similar paths going between siblings via their father as well as via mother). Such cases are filtered by skipSentence function.

The YenTNG branch is preparing the modules to be integrated into TNG (The Next Generation Genealogy System) and thus the modules are rearranged and changed to fit the TNG environment.

The functions are arranged in four PHP modules:
- index.php contains a form receiving parameters from the user
- Yen-BFS.php contains the functions calculating the next path, checking relevance of solution, and some other auxiliaries
- BFS.php contains biBFS calculation with auxiliary constructPath and getFam functions (the latter is useful for access of the graph data via SQL)
- parseGED.php creates the graph from a GEDCOM file (graph is a list of nodes each containing list of its neighbours).

Each module contains a "main" part which is used only if the module is run directly (for testing) and not included by the parent module.
 implementation of Yen's algorithm for computing K-shortest paths.
 
