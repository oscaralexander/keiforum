import axios from 'axios';

// Register Alpine components
import Share from './components/alpine/Share';
import Popout from './components/alpine/Popout';
import ConversationScroll from './components/alpine/ConversationScroll';
import AutoResize from './components/alpine/AutoResize';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener('alpine:init', () => {
    Alpine.data('share', Share);
    Alpine.data('popout', Popout);
    Alpine.data('conversationScroll', ConversationScroll);
    Alpine.data('autoResize', AutoResize);
});
