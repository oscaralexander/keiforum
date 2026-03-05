// Register Alpine components
import Editor from './components/alpine/Editor';
import MultiSelect from './components/alpine/MultiSelect';

const registerAlpineComponents = () => {
    Alpine.data('editor', Editor);
    Alpine.data('multiSelect', MultiSelect);
};

// In Safari, @livewireScripts (a regular script) boots Alpine and fires
// alpine:init before deferred ES modules in <head> execute, so the listener
// below would never be called. If Alpine is already initialised, register now.
if (window.Alpine) {
    registerAlpineComponents();
} else {
    document.addEventListener('alpine:init', registerAlpineComponents);
}
