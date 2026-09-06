import { reactive, ref } from 'vue';

/**
 * The state machine every admin list shares: fetch, open a form, save, delete.
 *
 * Six management screens landed at once, and hand-rolling this in each of them
 * would have reproduced exactly the copy-paste the rebuild set out to remove.
 * Pages supply the four calls that differ and keep their own markup.
 *
 * @param {object} options
 * @param {() => Promise<any>} options.list      Fetch the collection.
 * @param {(payload: any) => Promise<any>} options.create
 * @param {(key: any, payload: any) => Promise<any>} options.update
 * @param {(key: any) => Promise<any>} [options.remove]
 * @param {object} options.blank                 Empty form shape.
 * @param {(item: any) => object} [options.toForm]  Map a row onto the form.
 * @param {(item: any) => any} [options.keyOf]      Route key for update/delete.
 * @param {(item: any, created: boolean) => string} [options.savedMessage]
 */
export function useResourceCrud(options) {
    const items = ref([]);
    const loading = ref(true);
    const saving = ref(false);
    const editing = ref(null);
    const showForm = ref(false);
    const notice = ref(null);
    const errors = reactive({});
    const form = reactive({ ...options.blank });

    const keyOf = options.keyOf ?? ((item) => item.id);
    const toForm = options.toForm ?? ((item) => ({ ...item }));

    function clearErrors() {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    async function load() {
        loading.value = true;

        try {
            items.value = await options.list();
        } finally {
            loading.value = false;
        }
    }

    function startCreate() {
        editing.value = null;
        Object.assign(form, options.blank);
        clearErrors();
        showForm.value = true;
    }

    function startEdit(item) {
        editing.value = item;
        Object.assign(form, options.blank, toForm(item));
        clearErrors();
        showForm.value = true;
    }

    /**
     * @param {object} [payload] Overrides the form, for screens that build a
     *                           multipart body instead of posting it verbatim.
     */
    async function save(payload) {
        saving.value = true;
        notice.value = null;
        clearErrors();

        try {
            const body = payload ?? { ...form };
            const created = editing.value === null;

            const result = created
                ? await options.create(body)
                : await options.update(keyOf(editing.value), body);

            notice.value = options.savedMessage?.(result ?? form, created)
                ?? `${created ? 'Created' : 'Updated'} successfully.`;

            showForm.value = false;
            await load();

            return true;
        } catch (failure) {
            Object.assign(errors, failure.errors ?? {});

            // A refusal with no field errors -- a category still holding
            // products, say -- only has a message, so surface that instead.
            if (Object.keys(failure.errors ?? {}).length === 0) {
                notice.value = failure.message;
            }

            return false;
        } finally {
            saving.value = false;
        }
    }

    async function remove(item) {
        notice.value = null;

        try {
            const response = await options.remove(keyOf(item));

            notice.value = response?.message ?? 'Deleted.';
            await load();
        } catch (failure) {
            notice.value = failure.message;
        }
    }

    return {
        items, loading, saving, editing, showForm, notice, errors, form,
        load, startCreate, startEdit, save, remove, clearErrors,
    };
}
