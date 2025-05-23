import Ajax from 'core/ajax';
import notification from "core/notification";

/**
 * changeinternalcategory
 * @param {int} id
 * @returns {Promise<void>}
 */
export const changeinternalcategory = async (id) => {
    //AJAX call to webservice
    var selectelt = document.getElementById('internalcategory_'+id);
    await new Promise(resolve => {
        return Ajax.call([{
            methodname: 'block_my_external_backup_restore_courses_change_internalcategoryid',
            args: {
                'taskid': id,
                'internalcategoryid': selectelt.value,
            },
            done: result => {
                resolve(result);

            },
            fail: notification.exception
        }]);
    });

};

export const changestatus = async (id) => {
    //AJAX call to webservice
    var selectelt = document.getElementById('menustatus_select_'+id);
    await new Promise(resolve => {
        return Ajax.call([{
            methodname: 'block_my_external_backup_restore_courses_change_status',
            args: {
                'taskid': id,
                'status': selectelt.value,
            },
            done: result => {
                resolve(result);

            },
            fail: notification.exception
        }]);
    });

};