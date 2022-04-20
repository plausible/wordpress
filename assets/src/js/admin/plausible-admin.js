!(function (e) {
    var t = {};
    function n(a) {
        if (t[a]) return t[a].exports;
        var l = (t[a] = { i: a, l: !1, exports: {} });
        return e[a].call(l.exports, l, l.exports, n), (l.l = !0), l.exports;
    }
    (n.m = e),
        (n.c = t),
        (n.d = function (e, t, a) {
            n.o(e, t) || Object.defineProperty(e, t, { enumerable: !0, get: a });
        }),
        (n.r = function (e) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, { value: "Module" }), Object.defineProperty(e, "__esModule", { value: !0 });
        }),
        (n.t = function (e, t) {
            if ((1 & t && (e = n(e)), 8 & t)) return e;
            if (4 & t && "object" == typeof e && e && e.__esModule) return e;
            var a = Object.create(null);
            if ((n.r(a), Object.defineProperty(a, "default", { enumerable: !0, value: e }), 2 & t && "string" != typeof e))
                for (var l in e)
                    n.d(
                        a,
                        l,
                        function (t) {
                            return e[t];
                        }.bind(null, l)
                    );
            return a;
        }),
        (n.n = function (e) {
            var t =
                e && e.__esModule
                    ? function () {
                          return e.default;
                      }
                    : function () {
                          return e;
                      };
            return n.d(t, "a", t), t;
        }),
        (n.o = function (e, t) {
            return Object.prototype.hasOwnProperty.call(e, t);
        }),
        (n.p = ""),
        n((n.s = 0));
})([
    function (e, t, n) {
        n(1), (e.exports = n(2));
    },
    function (e, t, n) {},
    function (e, t) {
        document.addEventListener("DOMContentLoaded", function () {
            var e = document.getElementById("plausible-analytics-save-btn"),
                t = document.getElementById("plausible-analytics-settings-form");
            if (null !== t) {
                var n = t.querySelector(".plausible-analytics-admin-tabs"),
                    a = Array.from(n.querySelectorAll("a")),
                    l = Array.from(t.querySelectorAll(".plausible-analytics-content"));
                a.forEach(function (e) {
                    e.addEventListener("click", function (e) {
                        e.preventDefault();
                        var n = e.target.getAttribute("data-tab");
                        a.map(function (e) {
                            return e.classList.remove("active");
                        }),
                            l.map(function (e) {
                                return e.classList.remove("plausible-analytics-show");
                            }),
                            e.target.classList.add("active"),
                            t.querySelector("#plausible-analytics-content-".concat(n)).classList.add("plausible-analytics-show");
                    });
                });
                var i = t.querySelector('input[name="plausible_analytics_settings[custom_domain]"]'),
                    r = t.querySelector('input[name="plausible_analytics_settings[is_self_hosted_analytics]"]');
                e.addEventListener("click", function (n) {
                    n.preventDefault();
                    var a = new FormData(),
                        l = t.querySelector(".plausible-analytics-spinner"),
                        s = t.querySelector('input[name="plausible_analytics_settings[domain_name]"]').value,
                        u = t.querySelector('input[name="plausible_analytics_settings[custom_domain_prefix]"]').value,
                        o = null !== i && i.checked,
                        c = t.querySelector('input[name="plausible_analytics_settings[self_hosted_domain]"]').value,
                        d = null !== r && r.checked,
                        p = t.querySelector('input[name="plausible_analytics_settings[track_administrator]"]:checked'),
                        y = null !== p ? parseInt(p.value) : 0,
                        m = t.querySelector('input[name="plausible_analytics_settings[embed_analytics]"]:checked'),
                        f = null !== m ? parseInt(m.value) : 0,
                        b = null !== t.querySelector(".plausible-analytics-admin-settings-roadblock") ? document.querySelector(".plausible-analytics-admin-settings-roadblock").value : "",
                        _ = t.querySelector('input[name="plausible_analytics_settings[shared_link]"]'),
                        v = null !== _ ? _.value : 0;
                    (l.style.display = "block"),
                        e.setAttribute("disabled", "disabled"),
                        a.append("action", "plausible_analytics_save_admin_settings"),
                        a.append("roadblock", b),
                        a.append("domain_name", s),
                        a.append("custom_domain", !0 === o),
                        a.append("custom_domain_prefix", u),
                        a.append("is_self_hosted_analytics", !0 === d),
                        a.append("self_hosted_domain", c),
                        a.append("embed_analytics", 1 === f),
                        a.append("shared_link", v),
                        a.append("track_administrator", 1 === y),
                        fetch(ajaxurl, { method: "POST", body: a })
                            .then(function (e) {
                                return 200 === e.status && e.json();
                            })
                            .then(function (t) {
                                if(t.data.status == 'error') {
                                    e.querySelector("span").innerText = e.getAttribute("data-saved-error")
                                    setTimeout(function () {
                                        (l.style.display = "none"), e.removeAttribute("disabled"), (e.querySelector("span").innerText = e.getAttribute("data-default-text"));
                                    }, 3000);
                                }else{
                                    t.success && (e.querySelector("span").innerText = e.getAttribute("data-saved-text")),
                                    setTimeout(function () {
                                        (l.style.display = "none"), e.removeAttribute("disabled"), (e.querySelector("span").innerText = e.getAttribute("data-default-text"));
                                    }, 500);
                                }
                            });
                            
                });
            }
        });
    },
]);
