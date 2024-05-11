export class Comments {
    public button: HTMLButtonElement;
    public form: HTMLFormElement;
    constructor() {
        const button = document.getElementById("write-comment");
        if (!(button instanceof HTMLButtonElement)) {
            throw new Error("Element not found");
        }
        this.button = button;
        const form = document.getElementById("comment-form");
        if (!(form instanceof HTMLFormElement)) {
            throw new Error("Element not found");
        }
        this.form = form;
        this.button.addEventListener("click", this.reveal_comment_form);
    }

    public reveal_comment_form = ()=>{
        this.form.classList.remove("js-hidden");
        this.button.classList.add("js-hidden");
    }
}
