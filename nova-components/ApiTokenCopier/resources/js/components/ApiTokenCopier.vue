<template>
    <modal @modal-close="handleClose">
        <form
            @submit.prevent="handleConfirm"
            slot-scope="props"
            class="bg-white rounded-lg shadow-lg overflow-hidden"
            style="width: 460px"
        >
            <slot :uppercaseMode="uppercaseMode" :mode="mode">
                <div class="p-8">
                    <heading :level="2" class="heading">{{ __('Auth Token') }}</heading>
                    <code @click="handleCopy" class="token bg-primary-500">
                        {{ data.token }}
                    </code>
                    <p class="note">
                        {{ __('Click the token to copy it to your clipboard.') }}
                    </p>
                </div>
            </slot>

            <div class="bg-30 px-6 py-3 flex">
                <div class="ml-auto">
                    <button type="button" data-testid="cancel-button" dusk="cancel-general-button"
                            @click.prevent="handleClose"
                            class="btn bg-primary-500 text-80 text-gray-900 font-bold h-9 px-3 mr-3 btn-link border rounded">
                        {{ __('OK') }}
                    </button>
                </div>
            </div>
        </form>
    </modal>
</template>

<script>
export default {
    name: "GeneralModal",
    props: {
        data: {
            type: Object
        },
    },
    methods: {
        handleClose() {
            this.$emit('close')
        },
        handleCopy() {
            navigator.clipboard
                .writeText(this.data.token)
                .then(() => {
                    Nova.success('Token copied to clipboard');
                })
        },
    },
    /**
     * Mount the component.
     */
    mounted() {
    },
}
</script>

<style scoped>
    .heading {
        font-size: 2rem;
        margin: auto auto 1rem;
    }

    .token {
        padding: 2px 6px;
        border: solid 2px #e2e8f0;
        border-radius: 0.25rem;

        cursor: pointer;
        color: black;
        font-size: 0.85rem;
    }

    .note {
        margin-top: 1rem;
        font-size: 0.675rem;
    }
</style>
