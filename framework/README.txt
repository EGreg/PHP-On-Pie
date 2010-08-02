This document is about the Pie Framework, by Gregory Magarshak

DESIGN GOALS
------------

Pie offers a number of advantages over other frameworks, or not using any frameworks at all. Its design goals include:
  * Code Re-Use ... build apps out of plug-ins; make it easy to turn new development into a plugin
  * Don't Repeat Yourself ... one place to modify or handler things; applies to config, schemas, etc.
  * Consistent ... file locations; coding style; documentation
  * Easy To Learn ... close to PHP; one or two simple concepts, per feature; complex scenarios must build on simple ones
  * Scalable ... encourage efficient use of centralized resources like database
  * Responsive ... executes requests to app with minimal overhead, despite number of plugins, etc.
  * Extensible ... allow import of other code, especially the Zend Framework; sensible namespaces
  * Team Oriented ... supports division of labor; works well with revision control systems; continuous integration and testing
  * Portable ... easily deploy apps on various environments; including development environments

TECHNOLOGY STACK
----------------
Linux (can be changed to MacOS or Windows)
Apache 1.2 and up (can be changed to Lighthttpd, etc.)
MySQL 5 and up (currently the only supported DBMS)
PHP 5 and up


TODO FOR GREG
----

Study Zend Framework

Server-side rendering
	tools render HTML from the MVC -- done
	tools render javascript to initialize themselves -- done

Client-side instantiation
	tools create basic HTML if $('#'+prefix+'tool') is empty
	

Actions
	can do post - done
	response fills slots and wraps result in layout - done
		* what are the slot naming conventions?
