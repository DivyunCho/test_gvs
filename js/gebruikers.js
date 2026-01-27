const messages = document.querySelectorAll('.message');
messages.forEach(message => {
    setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    }, 3000);
});
