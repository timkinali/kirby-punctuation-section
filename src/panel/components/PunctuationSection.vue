<script>
export default {
  props: {
    fieldsets: {
      type: Object,
      required: true,
    },
    clipboard: {
      type: Boolean,
      default: false,
    },
    open: {
      type: Boolean,
      default: false,
    },
 /*    instructions: {
      type: Array
    }, */

  },

  data() {
    return {
      label: null,
      instructions: {
        'sv_SE': 'Klicka på ett märke för att kopiera',
        'en': 'Click a mark to copy it to clipboard'
      },
      text: [],
      theme: null,
      activeChar: null,
      categoryIndex: null,
      lastFocusedElement: null,
      lastSelection: null,
    };
  },

  created() {
    this.load().then(response => {
      //const response = await this.load();
      this.label = response.label;
      this.theme = response.theme || "none";
      this.instructions = this.t(this.instructions);
      //console.log(response.instructions);
      this.text = this.fieldsets.map((i) => ({
        ...i,
        label: this.t(i.label),
        help: i.help ? this.t(i.help) : false,
      }));
    });
  },

  mounted() {
    document.addEventListener("focus", this.handleEditabeFocus, {
      capture: true,
    });
    document.addEventListener("blur", this.handleInputBlur, {
      capture: true,
    });
    document.addEventListener("click", this.handleDocumentClick);
  },

  beforeDestroy() {
    document.removeEventListener("focus", this.handleEditabeFocus, {
      capture: true,
    });
    document.removeEventListener("blur", this.handleInputBlur, {
      capture: true,
    });
    document.removeEventListener("click", this.handleDocumentClick);
  },

  methods: {
    t(value) {
      console.log(value['sv_SE']);
      if (!value || typeof value === "string") return value;
      return value[this.$panel.translation.code] ?? Object.values(value)[0];
    },

    handleEditabeFocus(event) {
      const element = event.target;

      if (element.isContentEditable) {
        this.lastFocusedElement = element;
        this.lastSelection = window.getSelection().getRangeAt(0).cloneRange();
      }
    },

    handleInputBlur(event) {
      const element = event.target;

      if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
        this.lastFocusedElement = element;
        this.lastSelection = {
          start: element.selectionStart,
          end: element.selectionEnd,
        };
      }
    },

    handleDocumentClick(event) {
      const target = event.target;

      if (
        !(
          target.tagName === "BUTTON" && target.classList.contains("k-button")
        ) &&
        !target.isContentEditable &&
        target.tagName !== "INPUT" &&
        target.tagName !== "TEXTAREA"
      ) {
        this.lastFocusedElement = null;
        this.lastSelection = null;
      }
    },

    async writeToClipboard(char, categoryIndex) {
      this.activeChar = char;
      this.categoryIndex = categoryIndex;
      setTimeout(() => {
        this.activeChar = null;
        this.categoryIndex = null;
      }, 2000);

      if (this.clipboard) {
        try {
          // The Clipboard API is only available to secure contexts, it cannot be used
          // from a content script running on `http:`-pages, only `https:`-pages.
          // Setting a browser flag can allow HTTP pages to be interpreted as secure.
          await navigator.clipboard.writeText(char);
          window.panel.notification.success("Copied to clipboard!");
        } catch (error) {
          const message = `Failed writing "${char}" to clipboard. The Clipboard API is only available to secure contexts (HTTPS).`;
          console.error(message);
          this.$store.dispatch("notification/error", message);
        }
      } else if (this.lastFocusedElement) {
        if (this.lastFocusedElement.isContentEditable) {
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(this.lastSelection);

          const range = selection.getRangeAt(0);
          const textNode = document.createTextNode(char);
          range.insertNode(textNode);
          range.setStartAfter(textNode);
          range.setEndAfter(textNode);
          this.lastSelection = range.cloneRange();

          selection.removeAllRanges();
          selection.addRange(range);
        } else if (
          this.lastFocusedElement.tagName === "INPUT" ||
          this.lastFocusedElement.tagName === "TEXTAREA"
        ) {
          const start = this.lastFocusedElement.selectionStart;
          const end = this.lastFocusedElement.selectionEnd;

          this.lastFocusedElement.value =
            this.lastFocusedElement.value.substring(0, start) +
            char +
            this.lastFocusedElement.value.substring(end);

          // Set caret position after the inserted character
          this.lastFocusedElement.selectionStart =
            this.lastFocusedElement.selectionEnd = start + char.length;

          // Restore focus position
          this.lastFocusedElement.focus();
        }
      }
    },
  },
};
</script>

<template>
 <k-section>

    <k-box :theme="theme" align="start" class="kps-box">
      <details :open="open" class="kps-details">
        <summary class="k-label k-section-label kps-summary"><span class="kps-label">{{ label }}</span> </summary>
          <k-box :text="instructions" theme="none" icon="copy" class="k-help" style="margin: var(--spacing-2) 0 var(--spacing-4)"/>

          <div class="container kps-space-y-1">
            <div v-for="(category, index) in text" :key="index" class="group">
              <div class="kps-flex kps-items-center kps-gap-2">
                <span>{{ category.label }}:</span>
                <k-button-group class="kps-gap-1">
                  <k-button
                    v-for="(char, charIndex) in category.chars"
                    :key="charIndex"
                    variant="filled"
                    size="xs"
                    :class="[
                      index === categoryIndex &&
                        char === activeChar &&
                        'kps-outline kps-outline-2 kps-outline-[var(--color-focus)]',
                    ]"
                    @click="writeToClipboard(char, index)"
                  >
                    {{ char }}
                  </k-button>
                </k-button-group>
              </div>

              <k-text
                v-if="category.help"
                :html="category.help"
                class="kps-text-[var(--color-text-dimmed)]"
              />
            </div>
          </div>
      </details>
    </k-box>
  </k-section>
</template>

<style scoped>
.container {
  container-type: inline-size;
  width: 100%;
}

@container (min-width: 640px) {
  .group {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
  }
}
</style>
