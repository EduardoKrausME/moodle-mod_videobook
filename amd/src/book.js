// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Video Book player adapters and real watched-segment tracker.
 *
 * @module     mod_videobook/book
 * @package   mod_videobook
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification', 'core/str'], function (Ajax, Notification, Str) {
    const HEARTBEAT_SECONDS = 10;
    let youtubePromise;
    let vimeoPromise;

    const loadYoutube = () => {
        if (window.YT && window.YT.Player) {
            return Promise.resolve(window.YT);
        }
        if (youtubePromise) {
            return youtubePromise;
        }
        youtubePromise = new Promise((resolve, reject) => {
            const previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                if (typeof previous === 'function') {
                    previous();
                }
                resolve(window.YT);
            };
            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return youtubePromise;
    };

    const loadVimeo = (url) => {
        if (window.Vimeo && window.Vimeo.Player) {
            return Promise.resolve(window.Vimeo);
        }
        if (vimeoPromise) {
            return vimeoPromise;
        }
        vimeoPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => resolve(window.Vimeo);
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return vimeoPromise;
    };

    class Html5Adapter {
        constructor(root) {
            this.element = root.querySelector('[data-region="html5-player"]');
            this.lastTime = 0;
        }

        initialise(config) {
            if (!this.element) {
                return Promise.reject(new Error('HTML5 player element not found.'));
            }
            if (config.hls && this.element.canPlayType('application/vnd.apple.mpegurl') === '') {
                return Promise.reject(new Error('HLS playback is not supported by this browser.'));
            }
            this.element.addEventListener('timeupdate', () => {
                this.lastTime = this.element.currentTime;
            });
            return Promise.resolve(this);
        }

        play() {
            return this.element.play();
        }

        pause() {
            this.element.pause();
        }

        getCurrentTime() {
            return Number(this.element.currentTime || 0);
        }

        getDuration() {
            return Number(this.element.duration || 0);
        }

        getPlaybackRate() {
            return Number(this.element.playbackRate || 1);
        }

        seek(position) {
            this.element.currentTime = Math.max(0, Number(position || 0));
        }

        onPlay(handler) {
            this.element.addEventListener('play', handler);
        }

        onPause(handler) {
            this.element.addEventListener('pause', handler);
        }

        onEnded(handler) {
            this.element.addEventListener('ended', handler);
        }

        onTimeUpdate(handler) {
            this.element.addEventListener('timeupdate', () => handler(this.getCurrentTime()));
        }

        onSeek(handler) {
            let previous = this.getCurrentTime();
            this.element.addEventListener('seeking', () => handler(this.getCurrentTime(), previous));
            this.element.addEventListener('timeupdate', () => {
                previous = this.getCurrentTime();
            });
        }
    }

    class YoutubeAdapter {
        constructor(root, config) {
            this.root = root;
            this.config = config;
            this.handlers = {};
            this.current = 0;
            this.lastTime = 0;
            this.playing = false;
        }

        initialise() {
            const element = this.root.querySelector('[data-region="youtube-player"]');
            return loadYoutube().then((YT) => new Promise((resolve) => {
                this.player = new YT.Player(element, {
                    host: this.config.youtubehost,
                    videoId: this.config.youtubeid,
                    playerVars: {playsinline: 1, rel: 0, modestbranding: 1},
                    events: {
                        onReady: () => {
                            this.timer = window.setInterval(() => this.sample(), 500);
                            resolve(this);
                        },
                        onStateChange: (event) => {
                            if (event.data === YT.PlayerState.PLAYING) {
                                this.playing = true;
                                this.emit('play');
                            } else if (event.data === YT.PlayerState.PAUSED) {
                                this.playing = false;
                                this.emit('pause');
                            } else if (event.data === YT.PlayerState.ENDED) {
                                this.playing = false;
                                this.emit('ended');
                            }
                        }
                    }
                });
            }));
        }

        sample() {
            const current = this.getCurrentTime();
            if (this.playing && Math.abs(current - this.lastTime) > Math.max(3, this.getPlaybackRate() * 3)) {
                this.emit('seek', current, this.lastTime);
            }
            if (this.playing) {
                this.emit('timeupdate', current);
            }
            this.lastTime = current;
        }

        play() {
            this.player.playVideo();
            return Promise.resolve();
        }

        pause() {
            this.player.pauseVideo();
        }

        getCurrentTime() {
            return Number(this.player.getCurrentTime() || 0);
        }

        getDuration() {
            return Number(this.player.getDuration() || 0);
        }

        getPlaybackRate() {
            return Number(this.player.getPlaybackRate() || 1);
        }

        seek(position) {
            this.player.seekTo(Math.max(0, Number(position || 0)), true);
        }

        onPlay(handler) {
            this.on('play', handler);
        }

        onPause(handler) {
            this.on('pause', handler);
        }

        onEnded(handler) {
            this.on('ended', handler);
        }

        onTimeUpdate(handler) {
            this.on('timeupdate', handler);
        }

        onSeek(handler) {
            this.on('seek', handler);
        }

        on(name, handler) {
            this.handlers[name] = (this.handlers[name] || []).concat(handler);
        }

        emit(name, ...args) {
            (this.handlers[name] || []).forEach((handler) => handler(...args));
        }
    }

    class VimeoAdapter {
        constructor(root, config) {
            this.root = root;
            this.config = config;
            this.handlers = {};
            this.current = 0;
            this.duration = 0;
            this.rate = 1;
        }

        initialise() {
            const element = this.root.querySelector('[data-region="vimeo-player"]');
            return loadVimeo(this.config.vimeoplayerurl).then((Vimeo) => {
                const options = {id: Number(this.config.vimeoid), responsive: true};
                if (this.config.vimeohash) {
                    options.h = this.config.vimeohash;
                }
                this.player = new Vimeo.Player(element, options);
                this.player.on('play', () => this.emit('play'));
                this.player.on('pause', () => this.emit('pause'));
                this.player.on('ended', () => this.emit('ended'));
                this.player.on('timeupdate', (data) => {
                    this.current = data.seconds;
                    this.duration = data.duration;
                    this.emit('timeupdate', data.seconds);
                });
                this.player.on('seeked', (data) => {
                    const previous = this.current;
                    this.current = data.seconds;
                    this.emit('seek', data.seconds, previous);
                });
                this.player.on('playbackratechange', (data) => {
                    this.rate = data.playbackRate || 1;
                });
                return Promise.all([this.player.ready(), this.player.getDuration()]).then((values) => {
                    this.duration = values[1];
                    return this;
                });
            });
        }

        play() {
            return this.player.play();
        }

        pause() {
            return this.player.pause();
        }

        getCurrentTime() {
            return Number(this.current || 0);
        }

        getDuration() {
            return Number(this.duration || 0);
        }

        getPlaybackRate() {
            return Number(this.rate || 1);
        }

        seek(position) {
            return this.player.setCurrentTime(Math.max(0, Number(position || 0)));
        }

        onPlay(handler) {
            this.on('play', handler);
        }

        onPause(handler) {
            this.on('pause', handler);
        }

        onEnded(handler) {
            this.on('ended', handler);
        }

        onTimeUpdate(handler) {
            this.on('timeupdate', handler);
        }

        onSeek(handler) {
            this.on('seek', handler);
        }

        on(name, handler) {
            this.handlers[name] = (this.handlers[name] || []).concat(handler);
        }

        emit(name, ...args) {
            (this.handlers[name] || []).forEach((handler) => handler(...args));
        }
    }

    const createPlayer = (root, config) => {
        if (config.type === 'html5') {
            return (new Html5Adapter(root)).initialise(config);
        }
        if (config.type === 'youtube') {
            return (new YoutubeAdapter(root, config)).initialise();
        }
        if (config.type === 'vimeo') {
            return (new VimeoAdapter(root, config)).initialise();
        }
        return Promise.resolve(null);
    };

    class Tracker {
        constructor(root, config, player) {
            this.root = root;
            this.config = config;
            this.player = player;
            this.playing = false;
            this.pendingStart = null;
            this.pendingEnd = null;
            this.lastTime = Number(config.lastposition || 0);
            this.segments = Array.isArray(config.segments) ? config.segments : [];
            this.sending = false;
            this.queueKey = 'mod_videobook_queue_' + config.cmid;
            this.memoryQueue = [];
            this.storageAvailable = true;
        }

        initialise() {
            this.player.onPlay(() => {
                this.playing = true;
                this.lastTime = this.player.getCurrentTime();
                this.pendingStart = this.lastTime;
                this.pendingEnd = this.lastTime;
            });
            this.player.onTimeUpdate((current) => this.timeUpdate(Number(current)));
            this.player.onPause(() => {
                this.playing = false;
                this.flush('paused');
            });
            this.player.onEnded(() => {
                this.playing = false;
                this.flush('ended');
            });
            this.player.onSeek((current, previous) => this.seek(Number(current), Number(previous || this.lastTime)));
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.flush('hidden');
                }
            });
            window.addEventListener('pagehide', () => this.flush('closed'));
            window.addEventListener('online', () => this.drain());
            this.timer = window.setInterval(() => {
                if (this.playing) {
                    this.flush('playing');
                }
            }, HEARTBEAT_SECONDS * 1000);
            this.applyResume();
            this.drain();
        }

        timeUpdate(current) {
            if (!this.playing || !Number.isFinite(current)) {
                this.lastTime = current;
                return;
            }
            const rate = Math.max(0.25, this.player.getPlaybackRate());
            const delta = current - this.lastTime;
            if (delta >= 0 && delta <= Math.max(3, rate * 3)) {
                if (this.pendingStart === null) {
                    this.pendingStart = this.lastTime;
                }
                this.pendingEnd = current;
            }
            this.lastTime = current;
        }

        seek(current, previous) {
            if (!this.config.allowseek && Math.abs(current - previous) > 0.25 && !this.isWatched(current)) {
                this.player.seek(previous);
                this.showMessage('seekblocked');
                return;
            }
            this.flush('seeking');
            this.lastTime = current;
            this.pendingStart = this.playing ? current : null;
            this.pendingEnd = this.pendingStart;
        }

        flush(state) {
            if (!this.player) {
                return;
            }
            const duration = Number(this.player.getDuration() || 0);
            if (!Number.isFinite(duration) || duration <= 0) {
                return;
            }
            const current = Number(this.player.getCurrentTime() || 0);
            const start = this.pendingStart === null ? current : this.pendingStart;
            const end = this.pendingEnd === null ? start : this.pendingEnd;
            this.pendingStart = this.playing ? current : null;
            this.pendingEnd = this.pendingStart;
            this.enqueue({
                cmid: Number(this.config.cmid),
                chapterid: Number(this.config.chapterid),
                currentposition: current,
                duration: duration,
                playbackrate: Number(this.player.getPlaybackRate() || 1),
                segmentstart: Number(start || 0),
                segmentend: Number(Math.max(start, end) || 0),
                playerstate: state
            });
            this.drain();
        }

        enqueue(payload) {
            const queue = this.readQueue();
            queue.push(payload);
            this.writeQueue(queue);
        }

        drain() {
            if (this.sending || !navigator.onLine) {
                return;
            }
            const queue = this.readQueue();
            if (!queue.length) {
                return;
            }
            const pending = queue[0];
            this.sending = true;
            Ajax.call([{
                methodname: 'mod_videobook_update_progress',
                args: pending
            }])[0].then((response) => {
                const currentQueue = this.readQueue();
                currentQueue.shift();
                this.writeQueue(currentQueue);
                this.sending = false;
                if (Number(pending.chapterid) === Number(this.config.chapterid)) {
                    this.applyResponse(response);
                }
                this.drain();
            }).catch(() => {
                this.sending = false;
                this.showMessage('trackingerror');
            });
        }

        readQueue() {
            if (!this.storageAvailable) {
                return this.memoryQueue.slice();
            }
            try {
                const queue = JSON.parse(window.localStorage.getItem(this.queueKey) || '[]');
                return Array.isArray(queue) && queue.length ? queue : this.memoryQueue.slice();
            } catch (error) {
                this.storageAvailable = false;
                return this.memoryQueue.slice();
            }
        }

        writeQueue(queue) {
            this.memoryQueue = queue.slice();
            try {
                window.localStorage.setItem(this.queueKey, JSON.stringify(queue));
                this.memoryQueue = [];
                this.storageAvailable = true;
            } catch (error) {
                this.storageAvailable = false;
            }
        }

        applyResponse(response) {
            try {
                this.segments = JSON.parse(response.segments || '[]');
            } catch (error) {
                this.segments = [];
            }
            if (response.reason === 'seekblocked') {
                this.player.seek(Number(response.correctposition || 0));
                this.showMessage('seekblocked');
            }
            const chapterPercent = this.root.querySelector('[data-region="chapter-percent"]');
            const bookPercent = this.root.querySelector('[data-region="book-percent"]');
            if (chapterPercent) {
                chapterPercent.textContent = Math.round(Number(response.percent || 0)) + '%';
            }
            if (bookPercent) {
                bookPercent.textContent = Math.round(Number(response.bookpercent || 0)) + '%';
            }
            if (response.completed) {
                this.markCompleted();
            }
        }

        markCompleted() {
            const message = this.root.querySelector('[data-region="completion-message"]');
            if (message) {
                message.classList.remove('d-none');
            }
            const lockedNext = this.root.querySelector('[data-region="locked-next"]');
            if (lockedNext && lockedNext.dataset.nextUrl) {
                const link = document.createElement('a');
                link.className = 'btn btn-primary';
                link.href = lockedNext.dataset.nextUrl;
                Str.get_string('next', 'videobook').then((text) => {
                    link.textContent = text + ' →';
                });
                lockedNext.replaceWith(link);
            }
        }

        applyResume() {
            const position = Number(this.config.lastposition || 0);
            if (position <= 1 || Number(this.config.resumeplayback) === 0) {
                return;
            }
            if (Number(this.config.resumeplayback) === 1) {
                this.player.seek(position);
                return;
            }
            Promise.all([
                Str.get_string('resumequestion', 'videobook', this.formatTime(position)),
                Str.get_string('resumeyes', 'videobook'),
                Str.get_string('resumeno', 'videobook')
            ]).then((strings) => Notification.confirm('', strings[0], strings[1], strings[2],
                () => this.player.seek(position), () => this.player.seek(0)));
        }

        isWatched(position) {
            return this.segments.some((segment) => position >= Number(segment[0]) - 0.25 && position <= Number(segment[1]) + 0.25);
        }

        showMessage(key) {
            Str.get_string(key, 'videobook').then((message) => {
                const element = this.root.querySelector('[data-region="tracking-message"]');
                if (!element) {
                    return;
                }
                element.textContent = message;
                element.classList.remove('d-none');
                window.setTimeout(() => element.classList.add('d-none'), 5000);
            });
        }

        formatTime(seconds) {
            const value = Math.max(0, Math.round(seconds));
            const hours = Math.floor(value / 3600);
            const minutes = Math.floor((value % 3600) / 60);
            const remaining = value % 60;
            return (hours ? String(hours).padStart(2, '0') + ':' : '') +
                String(minutes).padStart(2, '0') + ':' + String(remaining).padStart(2, '0');
        }
    }

    const completeTextChapter = (root, config, button) => {
        button.disabled = true;
        Ajax.call([{
            methodname: 'mod_videobook_complete_chapter',
            args: {cmid: Number(config.cmid), chapterid: Number(config.chapterid)}
        }])[0].then((response) => {
            const bookPercent = root.querySelector('[data-region="book-percent"]');
            if (bookPercent) {
                bookPercent.textContent = Math.round(Number(response.bookpercent || 0)) + '%';
            }
            const message = root.querySelector('[data-region="completion-message"]');
            if (message) {
                message.classList.remove('d-none');
            }
            button.remove();
            const lockedNext = root.querySelector('[data-region="locked-next"]');
            if (lockedNext && lockedNext.dataset.nextUrl) {
                const link = document.createElement('a');
                link.className = 'btn btn-primary';
                link.href = lockedNext.dataset.nextUrl;
                Str.get_string('next', 'videobook').then((text) => {
                    link.textContent = text + ' →';
                });
                lockedNext.replaceWith(link);
            }
        }).catch((error) => {
            button.disabled = false;
            Notification.exception(error);
        });
    };

    const init = () => {
        document.querySelectorAll('[data-region="videobook"]').forEach((root) => {
            let config;
            try {
                config = JSON.parse(root.dataset.config || '{}');
            } catch (error) {
                Notification.exception(error);
                return;
            }

            const completeButton = root.querySelector('[data-action="complete-text"]');
            if (completeButton) {
                completeButton.addEventListener('click', () => completeTextChapter(root, config, completeButton));
            }

            if (!config.type || config.type === 'none') {
                return;
            }
            createPlayer(root, config).then((player) => {
                if (config.tracked && player) {
                    new Tracker(root, config, player).initialise();
                }
            }).catch((error) => {
                Notification.exception(error);
            });
        });
    };

    return {init: init};
});
