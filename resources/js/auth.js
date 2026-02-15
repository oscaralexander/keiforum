// Register Alpine components
import Editor from './components/alpine/Editor';
import MultiSelect from './components/alpine/MultiSelect';

document.addEventListener('alpine:init', () => {
    Alpine.data('editor', Editor);
    Alpine.data('multiSelect', MultiSelect);
});
