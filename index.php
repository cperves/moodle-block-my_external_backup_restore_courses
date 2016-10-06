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

require('../../config.php');
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');

require_login();
$url_token_checker =$CFG->wwwroot.'/blocks/my_external_backup_restore_courses/ajax/token_checker.php';

$context = context_system::instance();
require_capability('block/my_external_backup_restore_courses:view', $context);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/my_external_backup_restore_courses/index.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('externalmoodlecourselist', 'block_my_external_backup_restore_courses'));
$PAGE->set_heading(get_string('externalmoodlecourselist', 'block_my_external_backup_restore_courses'));
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('externalmoodlecourselist', 'block_my_external_backup_restore_courses'),$PAGE->url);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('externalmoodlecourselist','block_my_external_backup_restore_courses'));
echo $OUTPUT->box_start('my_external_backup_restore_course_refresh');
echo html_writer::link('#', html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('a/refresh'), 'alt'=>get_string('refresh'), 'class'=>'iconsmall')),array('onclick'=>'window.location=\''.$PAGE->url->out(false).'\';return false;'));
echo $OUTPUT->box_end();
echo $OUTPUT->box_start('my_external_backup_restore_course_help');
echo html_writer::tag('span', get_string('externalmoodlehelpsection','block_my_external_backup_restore_courses'));
echo $OUTPUT->box_end();
$systemcontext = context_system::instance();
$external_moodles_cfg = get_config('block_my_external_backup_restore_courses', 'external_moodles');// dmainname1,token1;domainname2;token2;...
$ws_params = array('username' => $USER->username);
//external cateory parameters
$config = get_config('block_my_external_backup_restore_courses');
$restorecourseinoriginalcategory= $config->restorecourseinoriginalcategory;
$categorytable=$config->categorytable;
$categorytable_foreignkey = $config->categorytable_foreignkey;
$categorytable_categoryfield = $config->categorytable_categoryfield;
$defaultcategoryid=$config->defaultcategory;

//check if plugin is correcly configured
if(empty($defaultcategoryid) || ($restorecourseinoriginalcategory == 1 && (empty($categorytable) || empty($categorytable_foreignkey) || empty($categorytable_foreignkey)))){
	print_error(get_string('misconfigured_plugin','block_my_external_backup_restore_courses'));
}

//forms paramteters
$submit = optional_param('submit', null, PARAM_TEXT);
$selectedcourses = optional_param_array('selectedcourses',null, PARAM_RAW);
$externalmoodleurl = optional_param('externalmoodleurl', null, PARAM_URL);
$externalmoodletoken  = optional_param('externalmoodletoken', null, PARAM_TEXT);
$allcourses  = optional_param_array('allcourses', null, PARAM_RAW); 
if($submit){
	if(isset($allcourses)){
		if(isset($selectedcourses)){
			foreach($selectedcourses as $selectedcourse){
				//check if already exists in db
				$dbinfo = $DB->get_record('block_external_backuprestore', array('userid'=>$USER->id, 'externalcourseid'=>$selectedcourse, 'externalmoodleurl'=>$externalmoodleurl));
				if(!$dbinfo){
					$datas = new stdClass();
					$datas->userid = $USER->id;
					$datas->externalcourseid = $selectedcourse;
					$datas->externalmoodleurl = $externalmoodleurl ;
					$datas->externalmoodletoken = $externalmoodletoken;
					$datas->internalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
					$datas->externalcoursename = optional_param('coursename_'.$selectedcourse, '', PARAM_TEXT);
					$datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
					$datas->timecreated = time();
					$DB->insert_record('block_external_backuprestore', $datas);
				}else{
					//update
					$datas = $dbinfo;
					$currentoriginalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
					$datas->internalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
					$currentoriginalstatus = $datas->status;
					
					if($datas->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED){
						$datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
					}
					if($datas->internalcategory!=$currentoriginalcategory || ($currentoriginalstatus == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED && $datas->status==block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED)){
						$datas->timemodified = time();
						$DB->update_record('block_external_backuprestore', $datas);
					}
				}
				
			}
		} 	
		//unselect
		foreach($allcourses as $currentcourse){
			
			if((isset($selectedcourses) && !in_array($currentcourse, $selectedcourses)) || !isset($selectedcourses)){
				$dbinfo = $DB->get_record('block_external_backuprestore', array('userid'=>$USER->id, 'externalcourseid'=>$currentcourse, 'externalmoodleurl'=>$externalmoodleurl));
				if($dbinfo){
					if($dbinfo->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED){
						//remove course entry
						$DB->delete_records('block_external_backuprestore', array('userid'=>$USER->id, 'externalcourseid'=>$currentcourse, 'externalmoodleurl'=>$externalmoodleurl));
					}
				}
			}
		}
	}
}

if ($external_moodles_cfg && !empty($external_moodles_cfg)) {
	//sceduled task informations
	$scheduledtask = core\task\manager::get_scheduled_task('block_my_external_backup_restore_courses\task\backup_restore_task');
	$nextrun=$scheduledtask->get_next_run_time();
	$scheduledtask_nextrun =$nextrun==0?get_string('asap', 'tool_task'):userdate($nextrun);
	//extract key/value
	$external_moodles = explode(';', $external_moodles_cfg);
	$nbr_opened_external_moodles=0;
	foreach($external_moodles as $key_value) {
		if(!empty($key_value)){
			$key_value = explode(',',$key_value);
			$domainname = $key_value[0]; 
			$token = $key_value[1]; 
			$serveroptions = array();
			$serveroptions['token'] = $token;
			$serveroptions['domainname'] = $domainname;
			$validusername=true;
			$courses=array();
			try{
				$sitename = block_my_external_backup_restore_courses_tools::external_backup_course_sitename($domainname, $token);
				try{
					$courses = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($domainname, $token, 
					'block_my_external_backup_restore_courses_get_courses', $ws_params);
				}catch(block_my_external_backup_restore_courses_invalid_username_exception $uex){
					$validusername=false;
				}
				$nbr_opened_external_moodles+=1;
				echo html_writer::start_tag('div', array('class' => 'mform my_external_backup_course_form'));
				echo html_writer::start_tag('fieldset');
				echo html_writer::tag('legend', $sitename);
				if($validusername && count($courses)==0){
					echo $OUTPUT->box_start('external_backup_courses_item');
					echo html_writer::tag('div', get_string('nocourses'), array('class' => 'external_backup_course_name'));
					echo $OUTPUT->box_end();
				}
				if(!$validusername){
					echo $OUTPUT->box_start('external_backup_courses_item');
					echo html_writer::tag('div', get_string('invalidusername','block_my_external_backup_restore_courses'), array('class' => 'external_backup_course_name'));
					echo $OUTPUT->box_end();
				}
				if($courses){
					echo $OUTPUT->box_start('external_backup_restore_courses_item');
					//preparing form
					$target = new moodle_url('/blocks/my_external_backup_restore_courses/index.php');
					$attributes = array('method'=>'POST', 'action'=>$target);
					$disabled = empty($options['previewonly']) ? array() : array('disabled' => 'disabled');

					echo html_writer::start_tag('form', $attributes);
					echo html_writer::empty_tag('input',array('type'=>'hidden', 'name'=> 'externalmoodleurl','value'=>$domainname));
					echo html_writer::empty_tag('input',array('type'=>'hidden', 'name'=> 'externalmoodletoken','value'=>$token));
					$course_table = new html_table();
					$course_table->attributes['class'] = 'admintable generaltable';
					$course_table->head=array(get_string('course'),get_string('choose_to_restore','block_my_external_backup_restore_courses'));
					if($restorecourseinoriginalcategory == 1){
						$course_table->head[]=get_string('keepcategory','block_my_external_backup_restore_courses');
					}
					$course_table->head[]=get_string('status');
					$course_table->head[]=get_string('executiontime','block_my_external_backup_restore_courses');
					$course_table->head[]=get_string('nextruntime', 'block_my_external_backup_restore_courses');
					
					
				}
				foreach($courses as $course) {
		        	if (isset($course->fullname)) {
		        		//scheduled info stored in bd
		        		$scheduledinfo = $DB->get_record('block_external_backuprestore', array('userid'=>$USER->id, 'externalcourseid'=>$course->id, 'externalmoodleurl'=>$domainname));
						//original category informations
						$originalcategory = false;
						if($restorecourseinoriginalcategory == 1 && $course->categoryidentifier!=null && !empty($categorytable) && !empty($categorytable_categoryfield) && !empty($categorytable_foreignkey)){
							$originalcategory=$DB->get_record_sql("select cat.* from {".$categorytable."} ct inner join {course_categories} cat on cat.id=ct.$categorytable_foreignkey where $categorytable_categoryfield=:categoryfield",array('categoryfield'=>$course->categoryidentifier));
								
						}
						echo html_writer::empty_tag('input',array('type'=>'hidden', 'name'=> 'allcourses[]','value'=>$course->id));
						echo html_writer::empty_tag('input',array('type'=>'hidden', 'name'=> 'coursename_'.$course->id,'value'=>$course->fullname));
						//preparing html table 
						$tablerow=new html_table_row();
						$course_tablecell=new html_table_cell();
						$course_tablecell->text = $course->fullname;
						$select_tablecell=new html_table_cell();
						$attr = array();
						if($scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED){
							$attr['disabled'] = 'true';
							//input same hidden field to post selected value if selected
							if($scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED){
								echo html_writer::empty_tag('input',array('type'=>'hidden', 'name'=> 'selectedcourses[]','value'=>$course->id));
							} 
						}
						$select_tablecell->text = html_writer::checkbox('selectedcourses[]', $course->id, $scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED? true: false,'', $attr);
						

						$category_tablecell=new html_table_cell();
						$attr = array();
						if(!$originalcategory || ($scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED  )){
							$attr['disabled'] = 'true';
						}
						$category_tablecell->text = html_writer::checkbox('originalcategory_'.$course->id, $originalcategory? $originalcategory->id: 0,$scheduledinfo? ($scheduledinfo->internalcategory !=0? true : false):($originalcategory?true:false),$originalcategory?$originalcategory->name:'',$attr);//TODO prechecked and freeze
						
						$state_tablecell = new html_table_cell();
						$state_tablecell->attributes['class']='nowrap';
						$nextruntime_tablecell = new html_table_cell();
						$nextruntime_tablecell->attributes['class']='nowrap';
						$executiontime_tablecell = new html_table_cell();
						$executiontime_tablecell->attributes['class']='nowrap';
						if($scheduledinfo){
							$state_tablecell->text = get_string('status_'.$scheduledinfo->status, 'block_my_external_backup_restore_courses');
							if($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS){
								$nextruntime_tablecell->text = $scheduledtask_nextrun;
							}
							if($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_ERROR || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED){
								$executiontime_tablecell->text = userdate($scheduledinfo->timescheduleprocessed);
							}
						}
						
						$tablerow->cells=array($course_tablecell,$select_tablecell,$category_tablecell,$state_tablecell,$nextruntime_tablecell, $executiontime_tablecell);
						$course_table->data[]=$tablerow;					
					}
				}
				if($courses){
					
					echo html_writer::table($course_table);
					echo html_writer::empty_tag('input',array('type'=>'submit','value'=>get_string('submit'), 'name'=>'submit'));
					echo html_writer::end_tag('form');
					echo $OUTPUT->box_end();
				}		
				echo html_writer::end_tag('fieldset');
				echo html_writer::end_tag('div');
			}catch(Exception $ex){
				continue;
			}
		}
	}
	if($nbr_opened_external_moodles==0){
		echo html_writer::start_tag('div', array('class' => 'notice'));
		echo get_string('noexternalmoodleconnected','block_my_external_backup_restore_courses');
		echo html_writer::end_tag('div');
	}	
}else{
	echo html_writer::start_tag('div', array('class' => 'notice'));
	echo get_string('noexternalmoodleconnected','block_my_external_backup_restore_courses');
	echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();