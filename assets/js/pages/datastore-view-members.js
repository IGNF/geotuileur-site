import axios from 'axios'
import flash from '../components/flash-messages'
import { Wait } from './../utils'

document.addEventListener("DOMContentLoaded", function () {
    const wait = new Wait({ iconClass: "icon-timer" });

    document.querySelectorAll('.remove-member').forEach((el) => {
        el.addEventListener('click', () => {
            const user = JSON.parse(el.dataset.user)
            const datastoreId = el.dataset.datastoreId

            const userFullName = `${user.first_name} ${user.last_name}`

            bootbox.confirm({
                title: Translator.trans("datastore.members.remove_user.modal.title"),
                message: Translator.trans("datastore.members.remove_user.modal.message", { name: userFullName }),
                buttons: {
                    confirm: { className: 'btn btn--plain btn--primary' },
                    cancel: { className: 'btn btn--ghost btn--gray' }
                },
                callback: (result) => {
                    if (result) {
                        const url = Routing.generate('plage_datastore_members_remove', { datastoreId: datastoreId, user_id: user._id })
                        wait.show()
                        axios
                            .delete(url)
                            .then(() => {
                                el.closest('li').remove()
                                flash.flashAdd(Translator.trans("datastore.members.remove_user.success", { name: userFullName }), 'success')
                            })
                            .catch(error => {
                                console.error(error.response);
                                flash.flashAdd(Translator.trans("datastore.members.remove_user.failure"), 'error')
                            })
                            .finally(() => {
                                wait.hide()
                            })
                    }
                }
            });
        })
    })
});
