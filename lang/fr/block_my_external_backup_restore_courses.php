<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Restaurer vos cours depuis des Moodle distants';
$string["roles_included_in_external_courses_search"]="Rôles du cours à inclure dans la recherche de cours d'un autre Moodle";
$string["roles_included_in_external_courses_search_Desc"]="Rôles du cours à inclure dans la recherche de cours d'un autre Moodle lors de la recherche sur les champs utilisateurs :shortnames délimités par des simple quote et séparés par des virgules";
$string['external_moodle']='Liste des Moodles externes auxquels se connecter';
$string['external_moodleDesc']='Une liste formattée des Moodles externes sous la forme moodle_url1,token_compte_webservice_moodle_externe1;moodle_url2,token_compte_webservice_moodle_externe2;...';
$string['my_external_backup_restore_courses:addinstance'] = 'Ajouter une instance du block Télécharger vos cours d\'un autre Moodle';
$string['my_external_backup_restore_courses:can_see_backup_courses']='voir les les backup de cours d\'un autre utilisateur';
$string['my_external_backup_restore_courses:can_retrieve_courses']='récupérer les fichiers d\'un autre utilisateur';
$string['my_external_backup_restore_courses:myaddinstance'] = 'Ajouter une instance du block Télécharger vos cours d\'un autre Moodle au Tableau de bord';
$string['noexternalmoodleconnected']='Pas de moodle externe connecté';
$string['externalmoodlecourselist']='Liste des cours d\'autres Moodle';
$string['externalmoodlehelpsection']=
'Dans le tableau ci-dessous :<ul><li> cochez pour sélectionner les cours distants que vous souhaitez restaurer sur la plate-forme courante</li><li>puis cliquez sur le bouton "Envoyer"</ul>
les cours sont alors programmés pour restauration.<br><br>
Vous pouvez à tout moment consulter l\'état de vos cours à restaurer (date programmée, restauration effectuée, ...).<br>
Une notification vous sera adressée lorsque vos cours seront restaurés.
';
$string['invalidusername']='Vous n\'avez pas de compte sur cette plate-forme';
$string['restore']='Restaurer un cours';
$string['restorecourses']='Restaurer des cours';
$string['choose_to_restore']='Selectionner pour restauration';
$string['keepcategory']='Conserver la catégorie de cours originale';
$string['restorecourseinoriginalcategory']='Restaure les cours dans leurs catégories si possible';
$string['restorecourseinoriginalcategory_desc']='Restaure les cours dans leurs catégories si possible. Ceci requiert le plugin local course_category_types (outil de la synchronisation des catégories à partir du système d\'information).';
$string['categorytable']='Database table name where category informations are stored';
$string['categorytable_desc']='Database table name where category informations are stored';
$string['categorytable_foreignkey']='Database table foreign key for category id';
$string['categorytable_foreignkey_desc']='Database table foreign key for category id';
$string['categorytable_categoryfield']='Unique database table field that represent category same for current and foreign moodle implied';
$string['categorytable_categoryfield_desc']='Unique database table field that represent category same for current and foreign moodle implied';
$string['defaultcategory']='Id de la catégorie où restaurer les cours par défaut';
$string['defaultcategory_desc']='Id de la catégorie où restaurer les cours par défaut';
$string['misconfigured_plugin']='Erreur de configuration du plugin';
$string['status_0']='Programmé';
$string['status_1']='En cours';
$string['status_2']='Effectué';
$string['status_-1']='Erreur';
$string['my_external_backup_restore_courses_task']='tâche du plugin restaurer vos cours depuis des Moodle distants';
$string['error_msg_admin']='Erreur pour le cours ayant un id externe à {$externalcourseid} et un id interne à {$courseid}, pour le site {$externalmoodleurl} , pour l\'utilisateur {$user} :\n{$message}';
$string['messageprovider:restorationsuccess']='Notifier qu\'un cours externe à été restauré avec succès';
$string['messageprovider:restorationfailed'] = 'Notifier qu\'un cours externe n\'a pas été restauré correctement';
$string['error_mail_subject']='[Moodle restauration de cours] : Erreurs lors de la restauration d\'un cours externe';
$string['error_mail_main_message']='Erreurs lors de la restauration du cours externe "{$a->externalcoursename}" depuis la plate-forme moodle {$a->externalmoodle} vers la plate-forme moodle {$a->localmoodle}.\nVoyez les détails suivants.\n';
$string['error_mail_task_error_message']='{$a->message}.\n';
$string['error_mail_task_error_message_courseid']='cours interne {$a->courseid} : {$a->message}.\n';
$string['success_mail_subject']='[Moodle restauration de cours] : un cours externe a été restauré avec succès';
$string['success_mail_main_message']='La restauration du cours "{$a->externalcoursename}" depuis la plate-forme moodle {$a->externalmoodle} vers la plate-forme moodle {$a->localmoodle} a été effectuée avec succès.';
$string['cantrestorecourseincategorycontext'] ='L\'utilisateur {$a->username} ne pas restaurer le cours "{$a->externalcoursename}" dans la catégorie "{$a->internalcategoryname}" car il n\'a pas la capcité moodle/course:create.\n Le cours serra restauré dans la categorie "{$a->defaultcategoryname}".';
$string['cantrestorecourseindefaultcategorycontext'] ='L\'utilisateur {$a->username} ne pas restaurer le cours "{$a->externalcoursename}" dans la catégorie par défaut "{$a->defaultcategoryname}" car il n\'a pas la capacité moodle/course:create.';
$string['notexistinginternalcategory'] = 'L\'utilisateur "{$a->username}" ne peu pas restaurer de cours dans la catégorie "{$a->internalcategoryname}" car la catégorie interne renseignéen\'existe plus\n. Le cours serra restauré dans la catégorie "{$a->defaultcategoryname}".';
$string['my_external_backup_restore_courses:view'] = 'Voir le bloc \'Restaurer vos cours depuis des Moodle distants\'';
$string['executiontime'] = 'Heure d\'éxécution prévue';
$string['timelimitedmod'] = 'Mode d\'éxécution avec limites dans le temps';
$string['timelimitedmod_desc'] = 'Le mode d\'éxécution avec limites dans le temps implique que la tâche associée qui importe et restore les cours depuis des moodles externesne fonctionnera qu\'entre les deux heures renseignées';
$string['limitstart'] = 'Heure d\'éxécution de démarrage';
$string['limitend'] = 'Heure d\'éxécution de fin';
$string['limitstart_desc'] = 'Heure d\'éxécution de démarrage';
$string['limitend_desc'] = 'Heure d\'éxécution de fin';
$string['nextruntime']='Effectuée le';
$string['warningstoowner']='Afficher les avertissements au propriétaire du cours restauré';
$string['warningstoowner_desc']='Afficher les avertissements au propriétaire du cours restauré';
$string['includeexternalurlinmail']='Inclure l\'url de la plate-forme externe dans les mails de notification';
$string['includeexternalurlinmail_desc']='Inclure l\'url de la plate-forme externe dans les mails de notification';
$string['maillocalmoodleinfo']='{$a->site} ({$a->siteurl})';
$string['mailexternalmoodleinfo']='{$a->externalmoodlesitename} ({$a->externalmoodleurl})';
$string['NA']='N/A';