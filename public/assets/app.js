const OTP_STORAGE_KEY = "corleone_demo_otp";
const OTP_TARGET_KEY = "corleone_otp_target";
const OTP_TYPE_KEY = "corleone_otp_type";
const OTP_NAME_KEY = "corleone_pending_user_name";

const inboxEmails = [
  {
    title: "Welcome to Corleone App Hub Mail",
    from: "Corleone App Hub Team",
    body: "Your secure mailbox is ready. You can receive workspace updates, system notifications, and team messages.",
  },
  {
    title: "OTP Verification Successful",
    from: "Security",
    body: "Your account verification was successful. This helps keep your mailbox protected.",
  },
  {
    title: "Project Workspace Invitation",
    from: "Douglas Hill",
    body: "You have been added to a Corleone App Hub workspace. Open your dashboard to view tasks, repositories, and updates.",
  },
];

const archivedEmails = [
  {
    title: "Repository Access Updated",
    from: "Corleone App Hub Admin",
    body: "Your access to the API prototype repository was updated for the current UI/UX phase.",
  },
  {
    title: "Team Sync Notes",
    from: "Project Lead",
    body: "The latest team sync covered OTP, mailbox, authentication, and chatbot screen responsibilities.",
  },
  {
    title: "Design Review Reminder",
    from: "Quality Review",
    body: "Please verify the prototype navigation flow before backend integration begins.",
  },
  {
    title: "Archived System Notice",
    from: "System",
    body: "This message is archived as sample content for the mailbox prototype.",
  },
];

let sentEmails = readSentEmails();
let currentBox = "inbox";
let otpAutoVerifyTimer = null;

document.addEventListener("DOMContentLoaded", () => {
  if (!document.querySelector("[data-server-otp='true']")) {
    setupOtpScreen();
  }

  setupOtpInputs();

  if (document.getElementById("mailList")) {
    loadMailbox();
  }
});

function route(name, fallback) {
  if (window.prototypeRoutes && window.prototypeRoutes[name]) {
    return window.prototypeRoutes[name];
  }

  return fallback;
}

function goTo(name, fallback) {
  window.location.href = route(name, fallback);
}

function valueOf(id) {
  const element = document.getElementById(id);
  return element ? element.value.trim() : "";
}

function setText(id, value) {
  const element = document.getElementById(id);

  if (element) {
    element.textContent = value;
  }
}

function savePrototypeUser(email, name = "Verified User", provider = "prototype") {
  localStorage.setItem("verified_user", email);
  localStorage.setItem("user_name", name);
  localStorage.setItem("auth_provider", provider);
}

function generateOtp() {
  return String(Math.floor(100000 + Math.random() * 900000));
}

function saveOtpSession(target, type, name = "") {
  const otp = generateOtp();

  sessionStorage.setItem(OTP_STORAGE_KEY, otp);
  sessionStorage.setItem(OTP_TARGET_KEY, target);
  sessionStorage.setItem(OTP_TYPE_KEY, type);

  if (name) {
    sessionStorage.setItem(OTP_NAME_KEY, name);
  }

  return otp;
}

function readOtpValue() {
  return sessionStorage.getItem(OTP_STORAGE_KEY) || localStorage.getItem("prototype_otp") || "";
}

function readOtpTarget() {
  return sessionStorage.getItem(OTP_TARGET_KEY) || localStorage.getItem("otp_target") || "your account";
}

function readOtpType() {
  return sessionStorage.getItem(OTP_TYPE_KEY) || localStorage.getItem("otp_type") || "otp";
}

function readPendingName() {
  return sessionStorage.getItem(OTP_NAME_KEY) || localStorage.getItem("pending_user_name") || "";
}

function clearOtpSession() {
  sessionStorage.removeItem(OTP_STORAGE_KEY);
  sessionStorage.removeItem(OTP_TARGET_KEY);
  sessionStorage.removeItem(OTP_TYPE_KEY);
  sessionStorage.removeItem(OTP_NAME_KEY);
  localStorage.removeItem("prototype_otp");
  localStorage.removeItem("otp_target");
  localStorage.removeItem("otp_type");
  localStorage.removeItem("pending_user_name");
}

function logoutUser() {
  sessionStorage.clear();
  localStorage.removeItem("verified_user");
  localStorage.removeItem("user_name");
  localStorage.removeItem("auth_provider");
  localStorage.removeItem("prototype_otp");
  localStorage.removeItem("otp_target");
  localStorage.removeItem("otp_type");
  localStorage.removeItem("pending_user_name");

  goTo("home", "/");
}

function loginWithEmail() {
  const email = valueOf("loginEmail");
  const password = valueOf("loginPassword");

  if (!email || !password) {
    alert("Please enter your email address and password.");
    return;
  }

  savePrototypeUser(email, email.split("@")[0] || "Verified User", "email");
  goTo("mailbox", "/mailbox");
}

function registerAccount() {
  const name = valueOf("registerName");
  const email = valueOf("registerEmail");
  const password = valueOf("registerPassword");

  if (!name || !email || !password) {
    alert("Please complete all registration fields.");
    return;
  }

  saveOtpSession(email, "registration", name);
  goTo("otpVerify", "/otp/verify");
}

function loginWithGoogle() {
  savePrototypeUser("google.user@gmail.com", "Google User", "google");
  goTo("mailbox", "/mailbox");
}

function sendPhoneOtp() {
  const phone = valueOf("phone");

  if (!phone) {
    alert("Please enter your phone number.");
    return;
  }

  saveOtpSession(phone, "phone");
  goTo("otpVerify", "/otp/verify");
}

function sendEmailOtp() {
  const email = valueOf("email");

  if (!email) {
    alert("Please enter your email address.");
    return;
  }

  saveOtpSession(email, "email");
  goTo("otpVerify", "/otp/verify");
}

function setupOtpScreen() {
  setText("otpTarget", readOtpTarget());
}

function setupOtpInputs() {
  const otpInputs = document.querySelectorAll(".otp");

  if (!otpInputs.length) {
    return;
  }

  const scheduleAutoVerify = () => {
    clearTimeout(otpAutoVerifyTimer);

    const serverOtpForm = otpInputs[0].closest("form[data-server-otp='true']");

    if (serverOtpForm) {
      return;
    }

    if (getOtpCode(otpInputs).length !== otpInputs.length) {
      return;
    }

    otpAutoVerifyTimer = setTimeout(() => {
      validateOtp();
    }, 250);
  };

  otpInputs.forEach((input, index) => {
    input.setAttribute("autocomplete", "one-time-code");

    input.addEventListener("focus", () => {
      input.select();
    });

    input.addEventListener("input", () => {
      input.value = input.value.replace(/[^0-9]/g, "").slice(0, 1);
      clearOtpMessage();

      if (input.value && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }

      if (input.value && index === otpInputs.length - 1) {
        input.blur();
      }

      scheduleAutoVerify();
    });

    input.addEventListener("keydown", (event) => {
      if (event.key === "Backspace" && !input.value && index > 0) {
        event.preventDefault();
        otpInputs[index - 1].focus();
        otpInputs[index - 1].value = "";
        clearOtpMessage();
      }
    });

    input.addEventListener("paste", (event) => {
      const digits = event.clipboardData.getData("text").replace(/[^0-9]/g, "").slice(0, otpInputs.length);

      if (!digits) {
        return;
      }

      event.preventDefault();
      digits.split("").forEach((digit, pasteIndex) => {
        otpInputs[pasteIndex].value = digit;
      });

      const nextIndex = Math.min(digits.length, otpInputs.length - 1);
      otpInputs[nextIndex].focus();
      clearOtpMessage();
      scheduleAutoVerify();
    });
  });
}

function getOtpCode(inputs = document.querySelectorAll(".otp")) {
  return Array.from(inputs).map((input) => input.value).join("");
}

function clearOtpMessage() {
  const message = document.getElementById("message");

  if (message) {
    message.textContent = "";
    message.removeAttribute("style");
  }
}

function validateOtp() {
  const inputs = document.querySelectorAll(".otp");
  const otp = getOtpCode(inputs);
  const message = document.getElementById("message");
  const storedOtp = readOtpValue();

  clearTimeout(otpAutoVerifyTimer);

  if (!storedOtp) {
    if (message) {
      message.textContent = "Please generate an OTP from phone or email verification first.";
      message.style.color = "#fbbf24";
    }

    return;
  }

  if (otp.length < inputs.length) {
    if (message) {
      message.textContent = "Please enter the complete 6-digit OTP.";
      message.style.color = "#fbbf24";
    }

    return;
  }

  if (otp === storedOtp) {
    const target = readOtpTarget() || "verified.user@example.com";
    const name = readPendingName() || target.split("@")[0] || "Verified User";

    savePrototypeUser(target, name, readOtpType());
    clearOtpSession();

    if (message) {
      message.textContent = "OTP verified successfully. Redirecting to mailbox...";
      message.style.color = "#7dd3fc";
    }

    setTimeout(() => {
      goTo("mailbox", "/mailbox");
    }, 900);
    return;
  }

  if (message) {
    message.textContent = "Invalid OTP. Please try again.";
    message.style.color = "#dc2626";
  }
}

function readSentEmails() {
  try {
    return JSON.parse(localStorage.getItem("sent_emails")) || [];
  } catch (error) {
    return [];
  }
}

function saveSentEmails() {
  localStorage.setItem("sent_emails", JSON.stringify(sentEmails));
}

function loadMailbox() {
  const verifiedUser = localStorage.getItem("verified_user") || "Verified User";
  setText("userEmail", verifiedUser);
  updateSentCount();
  showInbox();
}

function updateSentCount() {
  setText("sentCount", String(sentEmails.length));
}

function setActiveMenu(box) {
  document.querySelectorAll("[data-box]").forEach((item) => {
    item.classList.toggle("active", item.dataset.box === box);
  });
}

function currentEmails() {
  if (currentBox === "sent") {
    return sentEmails;
  }

  if (currentBox === "archived") {
    return archivedEmails;
  }

  if (currentBox === "drafts") {
    return [];
  }

  return inboxEmails;
}

function renderEmails(emails) {
  const list = document.getElementById("mailList");

  if (!list) {
    return;
  }

  list.innerHTML = "";
  clearPreview();

  if (emails.length === 0) {
    const empty = document.createElement("div");
    empty.className = "mail-item";

    const title = document.createElement("strong");
    title.textContent = "No emails found";

    const note = document.createElement("small");
    note.textContent = "This folder is empty.";

    empty.append(title, note);
    list.appendChild(empty);
    return;
  }

  emails.forEach((mail, index) => {
    const item = document.createElement("div");
    item.className = "mail-item";
    item.addEventListener("click", () => openEmail(mail, item));

    const title = document.createElement("strong");
    title.textContent = mail.title || mail.subject || "Untitled email";

    const meta = document.createElement("small");
    meta.textContent = mail.from ? `From: ${mail.from}` : `To: ${mail.to}`;

    item.append(title, meta);
    list.appendChild(item);

    if (index === 0) {
      item.classList.add("active");
      openEmail(mail, item);
    }
  });
}

function clearPreview() {
  setText("previewTitle", "Select an email");
  setText("previewMeta", "");
  setText("previewBody", "");
}

function openEmail(mail, element) {
  document.querySelectorAll(".mail-item").forEach((item) => {
    item.classList.remove("active");
  });

  if (element) {
    element.classList.add("active");
  }

  setText("previewTitle", mail.title || mail.subject || "Untitled email");
  setText("previewMeta", mail.from ? `From: ${mail.from}` : `To: ${mail.to}`);
  setText("previewBody", mail.body || "");
}

function showInbox() {
  currentBox = "inbox";
  setText("mailTitle", "Inbox");
  setActiveMenu("inbox");
  renderEmails(inboxEmails);
}

function showSent() {
  currentBox = "sent";
  setText("mailTitle", "Sent History");
  setActiveMenu("sent");
  renderEmails(sentEmails);
}

function showDrafts() {
  currentBox = "drafts";
  setText("mailTitle", "Drafts");
  setActiveMenu("drafts");
  renderEmails([]);
}

function showArchived() {
  currentBox = "archived";
  setText("mailTitle", "Archived");
  setActiveMenu("archived");
  renderEmails(archivedEmails);
}

function openCompose() {
  const modal = document.getElementById("composeModal");

  if (modal) {
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
  }

  const to = document.getElementById("composeTo");

  if (to) {
    to.focus();
  }
}

function closeCompose() {
  const modal = document.getElementById("composeModal");

  if (modal) {
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
  }
}

function sendEmail() {
  const to = valueOf("composeTo");
  const subject = valueOf("composeSubject");
  const body = valueOf("composeBody");

  if (!to || !subject || !body) {
    alert("Please complete all fields.");
    return;
  }

  sentEmails.unshift({
    to,
    subject,
    body,
    date: new Date().toLocaleString(),
  });

  saveSentEmails();
  updateSentCount();

  document.getElementById("composeTo").value = "";
  document.getElementById("composeSubject").value = "";
  document.getElementById("composeBody").value = "";

  closeCompose();
  showSent();
}

function filterMail() {
  const search = document.getElementById("searchMail");
  const keyword = search ? search.value.toLowerCase() : "";
  const filtered = currentEmails().filter((mail) => JSON.stringify(mail).toLowerCase().includes(keyword));

  renderEmails(filtered);
}

function sendChat() {
  const input = document.getElementById("chatInput");

  if (!input) {
    return;
  }

  const message = input.value.trim();

  if (!message) {
    return;
  }

  appendMessage(message, "user");
  input.value = "";
  showTyping();

  setTimeout(() => {
    removeTyping();
    appendMessage(generateBotReply(message), "bot");
  }, 900);
}

function quickAsk(text) {
  const input = document.getElementById("chatInput");

  if (input) {
    input.value = text;
  }

  sendChat();
}

function handleChatKey(event) {
  if (event.key === "Enter") {
    event.preventDefault();
    sendChat();
  }
}

function appendMessage(text, sender) {
  const chatWindow = document.getElementById("chatWindow");

  if (!chatWindow) {
    return;
  }

  const wrapper = document.createElement("div");
  wrapper.className = `chat-message ${sender}`;

  const avatar = document.createElement("div");
  avatar.className = "avatar";
  avatar.textContent = sender === "user" ? "You" : "AI";

  const bubble = document.createElement("div");
  bubble.className = "bubble";
  bubble.textContent = text;

  wrapper.append(avatar, bubble);
  chatWindow.appendChild(wrapper);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function showTyping() {
  const chatWindow = document.getElementById("chatWindow");

  if (!chatWindow) {
    return;
  }

  const typing = document.createElement("div");
  typing.className = "chat-message bot";
  typing.id = "typingIndicator";

  const avatar = document.createElement("div");
  avatar.className = "avatar";
  avatar.textContent = "AI";

  const bubble = document.createElement("div");
  bubble.className = "bubble";

  const dots = document.createElement("div");
  dots.className = "typing";
  dots.append(document.createElement("span"), document.createElement("span"), document.createElement("span"));

  bubble.appendChild(dots);
  typing.append(avatar, bubble);
  chatWindow.appendChild(typing);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function removeTyping() {
  const typing = document.getElementById("typingIndicator");

  if (typing) {
    typing.remove();
  }
}

function generateBotReply(message) {
  const text = message.toLowerCase();

  if (text.includes("email") || text.includes("summarize") || text.includes("mailbox")) {
    return "Your mailbox shows recent updates about OTP verification, workspace invitations, and Corleone App Hub activity notices.";
  }

  if (text.includes("sent")) {
    return "Your sent email history is available inside the mailbox page. This prototype stores composed messages in localStorage.";
  }

  if (text.includes("compose") || text.includes("draft")) {
    return "Start with a clear subject, write a short direct message, and end with a polite closing.";
  }

  if (text.includes("otp") || text.includes("verification")) {
    return "OTP verification confirms access to a phone number or email address before the user enters the mailbox.";
  }

  if (text.includes("login") || text.includes("auth")) {
    return "The current authentication screens are UI prototypes. They store a temporary session in localStorage for flow testing.";
  }

  return "I can help with Corleone App Hub mailbox summaries, email drafting, OTP verification, and navigation through this Laravel UI prototype.";
}
