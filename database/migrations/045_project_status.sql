
PS C:\Users\joepk\Downloads\microgreens-erp > PS><?php
< : The term '<' is not recognized as the name of a cmdlet, function, scrip
t file, or operable program. Check the spelling of the name, or if a path w
as included, verify that the path is correct and try again.
At line:1 char:1
+ <?php
+ ~
    + CategoryInfo          : ObjectNotFound: (<:String) [], CommandNotFou 
   ndException
    + FullyQualifiedErrorId : CommandNotFoundException
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>require 'app/includes/config.php';
require : The term 'require' is not recognized as the name of a cmdlet, fun
ction, script file, or operable program. Check the spelling of the name, or
 if a path was included, verify that the path is correct and try again.
At line:1 char:1
+ require 'app/includes/config.php';
+ ~~~~~~~
    + CategoryInfo          : ObjectNotFound: (require:String) [], Command 
   NotFoundException
    + FullyQualifiedErrorId : CommandNotFoundException
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>

PS C:\Users\joepk\Downloads\microgreens-erp > PS>$pdo = new PDO('sqlite:' . ERP_DB);
At line:1 char:26
+ $pdo = new PDO('sqlite:' . ERP_DB);
+                          ~
Unexpected token '.' in expression or statement.
At line:1 char:25
+ $pdo = new PDO('sqlite:' . ERP_DB);
+                         ~
Missing closing ')' in expression.
At line:1 char:34
+ $pdo = new PDO('sqlite:' . ERP_DB);
+                                  ~
Unexpected token ')' in expression or statement.
    + CategoryInfo          : ParserError: (:) [], ParentContainsErrorReco 
   rdException
    + FullyQualifiedErrorId : UnexpectedToken
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
At line:1 char:6
+ $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
+      ~
You must provide a value expression following the '-' operator.
At line:1 char:19
+ $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
+                   ~
Unexpected token '(' in expression or statement.
At line:1 char:37
+ $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
+                                     ~
Missing argument in parameter list.
    + CategoryInfo          : ParserError: (:) [], ParentContainsErrorReco 
   rdException
    + FullyQualifiedErrorId : ExpectedValueExpression
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>

PS C:\Users\joepk\Downloads\microgreens-erp > PS>$sql = file_get_contents('database/migrations/045_project_status.sql');
file_get_contents : The term 'file_get_contents' is not recognized as the n
ame of a cmdlet, function, script file, or operable program. Check the spel
ling of the name, or if a path was included, verify that the path is correc
t and try again.
At line:1 char:8
+ $sql = file_get_contents('database/migrations/045_project_status.sql' ...
+        ~~~~~~~~~~~~~~~~~
    + CategoryInfo          : ObjectNotFound: (file_get_contents:String) [ 
   ], CommandNotFoundException
    + FullyQualifiedErrorId : CommandNotFoundException
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>$pdo->exec($sql);
At line:1 char:6
+ $pdo->exec($sql);
+      ~
You must provide a value expression following the '-' operator.
At line:1 char:11
+ $pdo->exec($sql);
+           ~
Unexpected token '(' in expression or statement.
    + CategoryInfo          : ParserError: (:) [], ParentContainsErrorReco 
   rdException
    + FullyQualifiedErrorId : ExpectedValueExpression
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>

PS C:\Users\joepk\Downloads\microgreens-erp > PS>echo "Migratie uitgevoerd op: " . ERP_DB . PHP_EOL;
Migratie uitgevoerd op: 
.
ERP_DB
.
PHP_EOL

PS C:\Users\joepk\Downloads\microgreens-erp > PS>$rows = $pdo->query('SELECT * FROM project_status')->fetchAll(PDO::FETCH_ASSOC);
At line:1 char:14
+ $rows = $pdo->query('SELECT * FROM project_status')->fetchAll(PDO::FE ...
+              ~
You must provide a value expression following the '-' operator.
At line:1 char:20
+ $rows = $pdo->query('SELECT * FROM project_status')->fetchAll(PDO::FE ...
+                    ~
Unexpected token '(' in expression or statement.
At line:1 char:53
+ $rows = $pdo->query('SELECT * FROM project_status')->fetchAll(PDO::FE ...
+                                                     ~
You must provide a value expression following the '-' operator.
At line:1 char:62
+ ... rows = $pdo->query('SELECT * FROM project_status')->fetchAll(PDO::FET
 ...
+                                                                 ~
Unexpected token '(' in expression or statement.
    + CategoryInfo          : ParserError: (:) [], ParentContainsErrorReco 
   rdException
    + FullyQualifiedErrorId : ExpectedValueExpression
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>print_r($rows);
print_r : The term 'print_r' is not recognized as the name of a cmdlet, fun
ction, script file, or operable program. Check the spelling of the name, or
 if a path was included, verify that the path is correct and try again.
At line:1 char:1
+ print_r($rows);
+ ~~~~~~~
    + CategoryInfo          : ObjectNotFound: (print_r:String) [], Command 
   NotFoundException
    + FullyQualifiedErrorId : CommandNotFoundException
 

PS C:\Users\joepk\Downloads\microgreens-erp > PS>