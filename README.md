#CakePHP ACL ACO Check shell extension

*Author:* Abhishek Gupta
*Created:* 01/01/2013
*Version:* 1

***
Thank you for taking a look at this extension.If you have any questions, you can contact me at abhi.gupta200297@gmail.com
***

###Introduction<hr />
CakePHP ACL component is an excellent way to integrate access control in your web application. However, during the routine
development & maintenance of the project, there is no way to automate adding of new ACO's to the ACL. This is what inspired
me to write this extension. If you have used CakePHP ACL component, you probably know how tedious it is to enter every
ACO manually using the command line.

The purpose of this extension is to automate that part as much as possible.

###License<hr />
Please feel free to use or modify this code as per your needs. It is free for commercial or personal use

###System Requirements<hr />
Any computer with CakePHP 2.x installed. This extension works with DBAcl implementation of ACL

###Installation<hr />
1. Copy the file AcoCheckShell.php to App/Cosolle/Command/ directory of your CakePHP project
2. To check if its installed properly, run Console/cake from your app folder. aco_check should be visible in loaded extensions.

###Usage<hr />
Usage is very simple and self explanatory.
The extension assumes that you have a TOP LEVEL aco as shown in:
<a href=http://book.cakephp.org/2.0/en/tutorials-and-examples/simple-acl-controlled-application/simple-acl-controlled-application.html>Official CakePHP cookbook</a>

From the Cake APP folder, you have to run:
***
Console/cake aco_check
***

The extension will ask you the name of your root ACO. Provide the name of your root ACO here. Default is controllers.
The program will now scan all your Controllers and their methods, check which of them are missing in the ACL
and then display a list of the missing ones. It will give you an option to see command suggestions after this scan.
If you opt to see command suggestions, it will display a list of commands you should run to update all the ACO's.
If you want the extension to run all those commands for you, you can type in "yes" as an answer where the extension asks
you if it should run these suggestions automatically.

Your ACO's will now be in sync with the new Controllers/Methods in the project.
