import { jsOMS }      from '../../jsOMS/Utils/oLib.js';
import { Autoloader } from '../../jsOMS/Autoloader.js';
import { NotificationMessage } from '../../jsOMS/Message/Notification/NotificationMessage.js';
import { NotificationType } from '../../jsOMS/Message/Notification/NotificationType.js';
import { Upload } from './Models/Upload.js';

Autoloader.defineNamespace('omsApp.Modules');

/**
 * @todo Create immediate text preview similar to a rich text editor or Typora.
 *      https://github.com/Karaka-Management/oms-Editor/issues/4
 */
/* global omsApp */
omsApp.Modules.Media = class {
    /**
     * @constructor
     *
     * @since 1.0.0
     */
    constructor (app)
    {
        this.app = app;
    };

    bind (id)
    {
        const e = typeof id === 'undefined'
            ? document.getElementsByTagName('form')
            : [document.getElementById(id)];

        const length = e.length;

        for (let i = 0; i < length; ++i) {
            // this.bindElement(e[i]);
        }
    };

    bindElement (form)
    {
        if (typeof form === 'undefined' || !form) {
            jsOMS.Log.Logger.instance.error('Invalid form: ' + form, 'MediaController');

            return;
        }

        const self = this;

        if (!form.querySelector('input[type=file]')
            || !document.querySelector('input[type=file][form=' + form.id + ']')
        ) {
            try {
                // Inject media upload into form view
                this.app.uiManager.getFormManager().get(form.id).injectSubmit(function (e, requestId)
                {
                    /**
                     * @todo Karaka/Modules#198
                     *  The uploader should support multiple upload fields.
                     *  Currently only one is supported per form.
                     */

                    /** global: jsOMS */
                    const fileFields = document.querySelectorAll(
                            '#' + e.id + ' input[type=file], '
                            + 'input[form="' + e.id + '"][type=file]'
                        );
                    const uploader   = new Upload(self.app.responseManager);

                    uploader.setSuccess(e.id, function (type, response)
                    {
                        self.app.notifyManager.send(
                            new NotificationMessage(response[0].status, response[0].title, response[0].message), NotificationType.APP_NOTIFICATION
                        );

                        document.querySelector(
                            '#' + e.id + ' input[type=file]+input[type=hidden], '
                            + 'input[form="' + e.id + '"][type=file]+input[type=hidden]'
                        ).value = response[0].response;
                        self.app.eventManager.trigger(form.id, requestId);
                    });

                    const uploadData = document.querySelector(
                        '#' + e.id + ' input[type=file], '
                        + 'input[form="' + e.id + '"][type="file"]'
                    );

                    if (uploadData.hasAttribute('data-uri')) {
                        uploader.setUri(uploadData.getAttribute('data-uri'));
                    } else {
                        uploader.setUri('api/media');
                    }

                    const length   = fileFields.length;
                    let fileLength = 0;

                    for (let i = 0; i < length; ++i) {
                        fileLength = fileFields[i].files.length;
                        for (let j = 0; j < fileLength; ++j) {
                            uploader.addFile(fileFields[i].files[j]);
                        }
                    }

                    if (uploader.count() < 1) {
                        self.app.eventManager.trigger(form.id, requestId);
                        return;
                    }

                    uploader.upload(e.id);
                });
            } catch (e) {
                this.app.logger.info('Tried to add media upload support for form without an ID.');
            }
        }
    };
};

window.omsApp.moduleManager.get('Media').bind();
