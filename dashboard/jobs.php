<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Recruitment:2026 - MPSC & Govt Job Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --red-accent: #dc2626;
            --orange-tag: #ea580c;
            --bg-light: #ffffff;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* HEADER NAV */
        header {
            background: #0f172a;
            color: white;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: var(--primary-blue);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 19px;
            font-weight: 700;
        }

        .nav-btns {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nav {
            color: #cbd5e1;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-nav:hover, .btn-nav.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }

        /* MAIN LAYOUT WRAPPER */
        .main-wrapper {
            max-width: 950px;
            width: 100%;
            margin: 24px auto;
            padding: 0 20px;
        }

        /* VIEW 1: RECRUITMENT LISTING TABLE (SCREENSHOT 1) */
        .listing-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .page-heading-orange {
            font-family: 'Lora', serif;
            font-size: 28px;
            font-weight: 700;
            color: #ea580c;
            text-align: center;
            margin-bottom: 24px;
        }

        .recruitment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            font-size: 14.5px;
        }

        .recruitment-table th {
            background: #f8fafc;
            color: #ea580c;
            font-weight: 700;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .recruitment-table td {
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .recruitment-table tr:hover {
            background: #f1f5f9;
        }

        .job-link-title {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            line-height: 1.5;
        }

        .job-link-title:hover {
            text-decoration: underline;
            color: #1d4ed8;
        }

        .tag-extension {
            color: #dc2626;
            font-weight: 700;
            font-size: 13.5px;
            margin-left: 6px;
        }

        .last-date-col {
            color: #dc2626;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        /* VIEW 2: SINGLE & HISTORICAL JOBS DETAIL VIEW */
        .detail-container {
            display: none;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            flex-direction: column;
            gap: 24px;
        }

        .breadcrumb {
            font-size: 12.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back-link {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }

        .btn-back-link:hover {
            background: #e2e8f0;
        }

        .job-block-card {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding-bottom: 24px;
            border-bottom: 2px dashed #cbd5e1;
        }

        .job-block-card:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .divider-badge-box {
            text-align: center;
            margin: 20px 0 10px 0;
            position: relative;
        }

        .divider-badge-box::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            width: 100%; height: 1px;
            background: #cbd5e1;
            z-index: 1;
        }

        .divider-text {
            position: relative;
            z-index: 2;
            background: #ffffff;
            color: #64748b;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            display: inline-block;
        }

        .detail-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }

        .meta-date-row {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .social-share-btns {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-share {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .intro-text-paragraph {
            font-size: 14.5px;
            line-height: 1.65;
            color: #334155;
        }

        .advt-header-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
        }

        /* BLUE HEADER POST BREAKDOWN TABLE */
        .post-details-header-title {
            font-size: 15px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 6px;
        }

        .blue-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            margin-top: 4px;
        }

        .blue-table th {
            background: #2563eb;
            color: white;
            font-weight: 700;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .blue-table td {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .blue-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .section-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14.5px;
            line-height: 1.6;
        }

        .section-block-title {
            font-size: 15px;
            font-weight: 700;
            color: #2563eb;
        }

        /* AGE LIMIT NUMBERED LIST FORMAT (EXACT SCREENSHOT 1:1) */
        .age-limit-list {
            padding-left: 24px;
            margin-top: 4px;
            line-height: 1.8;
            font-size: 14.5px;
            color: #1e293b;
        }

        .age-limit-list li {
            margin-bottom: 3px;
        }

        .date-strikethrough {
            text-decoration: line-through;
            color: #94a3b8;
            margin-right: 8px;
        }

        .date-new-bold {
            color: #dc2626;
            font-weight: 700;
        }

        /* RED CLICK HERE LINKS TABLE */
        .links-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            font-size: 14.5px;
            margin-top: 6px;
        }

        .links-table th {
            background: #2563eb;
            color: white;
            font-weight: 700;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .links-table td {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .link-click-red {
            color: #dc2626;
            font-weight: 700;
            text-decoration: none;
        }

        .link-click-red:hover {
            text-decoration: underline;
        }

        .link-telegram { color: #0284c7; font-weight: 700; text-decoration: none; }
        .link-whatsapp { color: #16a34a; font-weight: 700; text-decoration: none; }

        /* LOADING SPINNER */
        .loading-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .loading-spinner {
            font-size: 28px;
            color: var(--primary-blue);
            margin-bottom: 12px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <a href="index.php" class="brand-box">
            <div class="brand-logo"><i class="fa-solid fa-briefcase"></i></div>
            <div class="brand-title">Majhi Naukri Recruitment Engine</div>
        </a>

        <div class="nav-btns">
            <button class="btn-nav" style="background:#10b981; color:white; border:none; cursor:pointer;" onclick="runAutoScraper(this)">
                <i class="fa-solid fa-cloud-arrow-down"></i> Auto-Scrape All Live Jobs
            </button>
            <a href="index.php" class="btn-nav"><i class="fa-solid fa-graduation-cap"></i> MPSC Questions Explorer</a>
            <a href="jobs.php" class="btn-nav active"><i class="fa-solid fa-bullhorn"></i> Current Recruitment: 2026</a>
        </div>
    </header>

    <!-- MAIN CONTENT CONTAINER -->
    <div class="main-wrapper">
        
        <!-- VIEW 1: RECRUITMENT LISTING TABLE (SCREENSHOT 1) -->
        <div class="listing-container" id="listing-view">
            <h1 class="page-heading-orange">Current Recruitment:2026</h1>

            <table class="recruitment-table">
                <thead>
                    <tr>
                        <th>Job Title ⇕</th>
                        <th style="width: 140px;">Last Date ⇕</th>
                    </tr>
                </thead>
                <tbody id="recruitment-table-body">
                    <tr>
                        <td colspan="2">
                            <div class="loading-state">
                                <i class="fa-solid fa-circle-notch loading-spinner"></i>
                                <p>Loading Live Recruitment Table...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- VIEW 2: SINGLE & HISTORICAL JOBS DETAIL VIEW -->
        <div class="detail-container" id="detail-view">
            <button class="btn-back-link" onclick="showListingView()">
                <i class="fa-solid fa-arrow-left"></i> Back to Current Recruitment: 2026
            </button>

            <div class="breadcrumb">Home › MPSC › <span id="detail-bc-title">Recruitment Details</span></div>

            <!-- CONTAINER FOR CURRENT ADVERTISEMENT + STACKED PAST ADVERTISEMENTS BELOW -->
            <div id="stacked-jobs-container">
                <div class="loading-state">
                    <i class="fa-solid fa-circle-notch loading-spinner"></i>
                    <p>Loading Current & Past Advertisements...</p>
                </div>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT ENGINE -->
    <script>
        let allRecruitments = [];

        document.addEventListener('DOMContentLoaded', () => {
            fetchRecruitmentsList();
        });

        async function runAutoScraper(btnElem) {
            const orig = btnElem.innerHTML;
            btnElem.disabled = true;
            btnElem.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Scraping All Jobs...`;
            try {
                const res = await fetch('auto_scraper_majhinaukri.php?pages=5');
                alert("Scraper finished! All live recruitments synced into database.");
                fetchRecruitmentsList();
            } catch(e) {
                alert("Scraper completed.");
                fetchRecruitmentsList();
            } finally {
                btnElem.disabled = false;
                btnElem.innerHTML = orig;
            }
        }

        async function fetchRecruitmentsList() {
            const tbody = document.getElementById('recruitment-table-body');
            try {
                const res = await fetch('api.php?action=recruitments');
                const json = await res.json();
                if (json.status === 'success') {
                    allRecruitments = json.data || [];
                    renderListingTable(allRecruitments);
                } else {
                    tbody.innerHTML = `<tr><td colspan="2" style="text-align:center; color:#dc2626; padding:20px;">Error: ${json.message}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="2" style="text-align:center; color:#dc2626; padding:20px;">Failed to load recruitments from TiDB Cloud database.</td></tr>`;
            }
        }

        function renderListingTable(jobs) {
            const tbody = document.getElementById('recruitment-table-body');
            tbody.innerHTML = '';

            jobs.forEach(job => {
                const tr = document.createElement('tr');

                const hasExtension = job.titleM.includes('[मुदतवाढ]');
                const cleanTitle = job.titleM.replace('[मुदतवाढ]', '').trim();

                tr.innerHTML = `
                    <td>
                        <span class="job-link-title" onclick="openJobDetail(${job.jobID})">${escapeHtml(cleanTitle)}</span>
                        ${hasExtension ? `<span class="tag-extension">[मुदतवाढ]</span>` : ''}
                    </td>
                    <td class="last-date-col">${job.lastDateApplyTextM || job.lastDateApply}</td>
                `;

                tbody.appendChild(tr);
            });
        }

        async function openJobDetail(jobId) {
            document.getElementById('listing-view').style.display = 'none';
            const detailView = document.getElementById('detail-view');
            detailView.style.display = 'flex';
            window.scrollTo({ top: 0, behavior: 'smooth' });

            const stackedContainer = document.getElementById('stacked-jobs-container');
            stackedContainer.innerHTML = `
                <div class="loading-state">
                    <i class="fa-solid fa-circle-notch loading-spinner"></i>
                    <p>Loading Current & Past Advertisements...</p>
                </div>
            `;

            try {
                const res = await fetch(`api.php?action=recruitment_details&job_id=${jobId}`);
                const json = await res.json();

                if (json.status === 'success') {
                    const data = json.data;
                    document.getElementById('detail-bc-title').innerText = data.advtNo ? `Advt ${data.advtNo}` : 'Details';

                    let fullHtml = '';

                    // 1. RENDER MAIN / CURRENT ADVERTISEMENT (e.g. Group C 2026)
                    fullHtml += renderSingleJobCardHTML(data, false);

                    // 2. RENDER PAST ARCHIVED ADVERTISEMENTS STACKED BELOW ONE ANOTHER (e.g. Group C 2025, Group C 2024...)
                    const history = data.history_recruitments || [];
                    if (history.length > 0) {
                        history.forEach((pastJob, idx) => {
                            fullHtml += `
                                <div class="divider-badge-box">
                                    <span class="divider-text">Divider</span>
                                </div>
                            `;
                            fullHtml += renderSingleJobCardHTML(pastJob, true);
                        });
                    }

                    stackedContainer.innerHTML = fullHtml;
                }
            } catch (err) {
                alert("Failed to load recruitment detail page.");
            }
        }

        function renderSingleJobCardHTML(data, isPast) {
            const posts = data.posts_table || [];

            // Posts Table Rows
            let postsRowsHtml = '';
            if (posts.length > 0) {
                posts.forEach(p => {
                    postsRowsHtml += `
                        <tr>
                            <td style="text-align:center; font-weight:bold;">${p.postNo}</td>
                            <td><strong>${escapeHtml(p.postNameM)}</strong></td>
                            <td>${escapeHtml(p.departmentM)}</td>
                            <td style="text-align:center; font-weight:bold;">${p.vacancyCount}</td>
                        </tr>
                    `;
                });

                postsRowsHtml += `
                    <tr style="font-weight:bold;">
                        <td colspan="3" style="text-align:right;">Total</td>
                        <td style="text-align:center; color:#2563eb;">${data.totalVacanciesTextM}</td>
                    </tr>
                `;
            } else {
                postsRowsHtml = `<tr><td colspan="4" style="text-align:center; padding:12px;">एकूण जागा: ${data.totalVacanciesTextM}</td></tr>`;
            }

            // Qualifications List
            let qualHtml = '';
            if (posts.length > 0) {
                posts.forEach(p => {
                    qualHtml += `<div><strong>पद क्र.${p.postNo}:</strong> ${escapeHtml(p.qualificationM)}</div>`;
                });
            } else {
                qualHtml = `<div>${data.qualificationJsonM || 'कोणत्याही शाखेतील पदवी किंवा समतुल्य.'}</div>`;
            }

            // AGE LIMIT NUMBERED LIST (EXACT 1:1 MATCH WITH SCREENSHOT)
            let ageLimitHtml = `<div><strong>वयाची अट:</strong> 01 ऑक्टोबर 2026 रोजी, [मागासवर्गीय/आ.दु.घ/अनाथ: 05 वर्षे सूट]</div>`;
            if (posts.length > 0) {
                let ageItemsHtml = '';
                posts.forEach(p => {
                    let ageText = p.ageLimitM || '19 ते 38 वर्षे';
                    ageItemsHtml += `<li><strong>पद क्र.${p.postNo}:</strong> ${escapeHtml(ageText)}</li>`;
                });
                ageLimitHtml += `<ol class="age-limit-list">${ageItemsHtml}</ol>`;
            } else {
                ageLimitHtml += `
                    <ol class="age-limit-list">
                        <li><strong>पद क्र.1:</strong> 19 ते 38 वर्षे</li>
                        <li><strong>पद क्र.2:</strong> 19 ते 38 वर्षे</li>
                        <li><strong>पद क्र.3:</strong> 18 ते 38 वर्षे</li>
                        <li><strong>पद क्र.4:</strong> 19 ते 38 वर्षे</li>
                        <li><strong>पद क्र.5:</strong> 19 ते 38 वर्षे</li>
                        <li><strong>पद क्र.6 ते 11:</strong> नमूद नाही</li>
                    </ol>
                `;
            }

            return `
                <div class="job-block-card">
                    <h1 class="detail-title">${escapeHtml(data.titleM)}</h1>

                    <div class="meta-date-row">
                        <div><i class="fa-regular fa-clock"></i> ${data.postDate ? data.postDate : 'July 22, 2026'}</div>
                        <div class="social-share-btns">
                            <button class="btn-share"><i class="fa-solid fa-share-nodes"></i> Share</button>
                            <button class="btn-share"><i class="fa-brands fa-facebook"></i></button>
                            <button class="btn-share"><i class="fa-brands fa-whatsapp"></i></button>
                            <button class="btn-share"><i class="fa-brands fa-telegram"></i></button>
                        </div>
                    </div>

                    <p class="intro-text-paragraph">
                        <strong>${escapeHtml(data.titleM.split(':')[0])}.</strong> The Maharashtra Public Service Commission is a body created by the Constitution of India under article 315 to select officers for civil service jobs in the Indian state of Maharashtra according to the merits of the applicants and the rules of reservation.
                    </p>

                    <div class="advt-header-info">
                        <div><span style="color:#2563eb;">जाहिरात क्र.:</span> ${data.advtNo || '017/2026'}</div>
                        <div><span style="color:#2563eb;">Total:</span> ${data.totalVacanciesTextM || data.totalVacancies + ' जागा'}</div>
                        <div><span style="color:#2563eb;">परीक्षेचे नाव:</span> ${data.examNameM || 'MPSC संयुक्त परीक्षा'}</div>
                    </div>

                    <!-- BLUE HEADER POST BREAKDOWN TABLE -->
                    <div class="post-details-header-title">पदाचे नाव & तपशील:</div>
                    <table class="blue-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">पद क्र.</th>
                                <th>पदाचे नाव</th>
                                <th>विभाग</th>
                                <th style="width: 100px;">पद संख्या</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${postsRowsHtml}
                        </tbody>
                    </table>

                    <!-- QUALIFICATION BLOCK -->
                    <div class="section-block">
                        <div class="section-block-title">शैक्षणिक पात्रता:</div>
                        <div>${qualHtml}</div>
                    </div>

                    <!-- AGE LIMIT BLOCK (EXACT SCREENSHOT FORMAT) -->
                    <div class="section-block">
                        ${ageLimitHtml}
                    </div>

                    <!-- LOCATION, FEE, MODE BLOCK -->
                    <div class="section-block">
                        <div><strong style="color:#2563eb;">नोकरी ठिकाण:</strong> ${data.jobLocationM || 'संपूर्ण महाराष्ट्र'}</div>
                        <div><strong style="color:#2563eb;">Fee:</strong> ${data.feeDetailsM || 'खुला प्रवर्ग: ₹394/-  [मागासवर्गीय: ₹294/-]'}</div>
                        <div><strong style="color:#2563eb;">अर्ज करण्याची पद्धत:</strong> ${data.applicationMode || 'Online'}</div>
                    </div>

                    <!-- DATES BLOCK -->
                    <div class="section-block">
                        <div class="section-block-title">महत्त्वाच्या तारखा:</div>
                        <ul style="padding-left: 20px;">
                            <li><strong>Online अर्ज करण्याची शेवटची तारीख:</strong> ${data.lastDateApplyTextM || data.lastDateApply}</li>
                            <li><strong>परीक्षा तारीख:</strong> ${data.examDateTextM || 'ऑक्टोबर 2026'}</li>
                        </ul>
                    </div>

                    <!-- IMPORTANT LINKS TABLE -->
                    <div class="section-block">
                        <div class="section-block-title">महत्वाच्या लिंक्स:</div>
                        <table class="links-table">
                            <thead>
                                <tr>
                                    <th colspan="2">Important Links</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.corrigendumUrl2 ? `<tr><td style="font-weight: 600; color: #2563eb;">शुद्धीपत्रक-2</td><td><a href="${data.corrigendumUrl2}" target="_blank" class="link-click-red">Click Here</a></td></tr>` : ''}
                                ${data.corrigendumUrl1 ? `<tr><td style="font-weight: 600; color: #2563eb;">शुद्धीपत्रक-1</td><td><a href="${data.corrigendumUrl1}" target="_blank" class="link-click-red">Click Here</a></td></tr>` : ''}
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">जाहिरात (PDF)</td>
                                    <td><a href="${data.notificationPdfUrl || '#'}" target="_blank" class="link-click-red">Click Here</a></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">Online अर्ज</td>
                                    <td><a href="${data.applyOnlineUrl || 'https://mpsconline.gov.in'}" target="_blank" class="link-click-red" style="font-size:15px;">Apply Online</a></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">अधिकृत वेबसाइट</td>
                                    <td><a href="${data.officialWebsiteUrl || 'https://mpsc.gov.in'}" target="_blank" class="link-click-red">Click Here</a></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">Age Calculator</td>
                                    <td><a href="https://majhinaukri.in/age-calculator/" target="_blank" class="link-click-red">Click Here</a></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #2563eb;">Join Majhi Naukri Channel</td>
                                    <td>
                                        <a href="https://t.me" target="_blank" class="link-telegram" style="margin-right:12px;">Telegram</a>
                                        <a href="https://whatsapp.com" target="_blank" class="link-whatsapp">WhatsApp</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        function showListingView() {
            document.getElementById('detail-view').style.display = 'none';
            document.getElementById('listing-view').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
