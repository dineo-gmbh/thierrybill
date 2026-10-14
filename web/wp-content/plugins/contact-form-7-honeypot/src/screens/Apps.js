import { useCallback, useEffect, useMemo, useRef, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { getApps } from "../api/api";
import CF7AppsSkeletonLoader from "../components/CF7AppsSkeletonLoader";
import CF7AppsApp from "../components/CF7AppsApp";
import CF7AppsNotice from "../components/CF7AppsNotice";
import { isPaymentApp } from "../utils/appCardTheme";
import { useParams } from "react-router";

const FILTER_TABS = [
	{ id: "all", label: __("All", "cf7apps"), icon: null },
	{
		id: "general",
		label: __("General", "cf7apps"),
		icon: "filter-general.svg",
	},
	{
		id: "spam-protection",
		label: __("Spam Protection", "cf7apps"),
		icon: "filter-spam.svg",
	},
	{
		id: "integrations",
		label: __("Integrations", "cf7apps"),
		icon: "filter-integrations.svg",
	},
	{
		id: "payments",
		label: __("Payment", "cf7apps"),
		icon: "filter-payments.svg",
	},
];

const GRID_EXIT_MS = 180;
const GRID_ENTER_MS = 320;
/** Longer display so users can read/install ACF (HFCF7-563). */
const ACF_NOTICE_MS = 10000;

const normalizeParent = (parentMenu) =>
	String(parentMenu || "")
		.toLowerCase()
		.replace(/\s+/g, "-");

const mapRouteParentToFilter = (parent) => {
	if (parent === "integration" || parent === "integrations") {
		return "integrations";
	}
	if (parent === "payment" || parent === "payment-gate") {
		return "payments";
	}
	return parent;
};

const getAcfInstallNoticeHtml = () =>
	sprintf(
		__(
			"Advanced Custom Fields (ACF) is required for this integration. Install and activate the ACF plugin to continue. %s",
			"cf7apps"
		),
		'<a class="cf7apps-acf-notice-link" href="' +
			(window.location.origin +
				"/wp-admin/plugin-install.php?s=advanced-custom-fields&tab=search&type=term") +
			'">' +
			__("Install ACF Plugin", "cf7apps") +
			"</a>"
	);

const CARD_SKELETON = (
	<>
		<div className="cf7apps-app">
			<CF7AppsSkeletonLoader width="100%" height={180} />
		</div>
		<div className="cf7apps-app">
			<CF7AppsSkeletonLoader width="100%" height={180} />
		</div>
	</>
);

const Apps = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [apps, setApps] = useState([]);
	const [showAcfNotice, setShowAcfNotice] = useState(false);
	const [searchQuery, setSearchQuery] = useState("");
	const [activeFilter, setActiveFilter] = useState("all");
	const [displayApps, setDisplayApps] = useState([]);
	const [gridTransition, setGridTransition] = useState("");
	const skipGridAnimation = useRef(true);
	const gridTimers = useRef([]);
	const { parent } = useParams();
	const assetsBase = CF7Apps?.assetsURL || "";

	const toolbarIcon = useCallback(
		(file) => (file && assetsBase ? `${assetsBase}/images/dashboard/toolbar/${file}` : ""),
		[assetsBase]
	);

	useEffect(() => {
		if (!showAcfNotice) {
			return;
		}

		const timer = setTimeout(() => {
			window.scrollTo({ top: 0, behavior: "smooth" });
		}, 100);

		return () => clearTimeout(timer);
	}, [showAcfNotice]);

	useEffect(() => {
		let cancelled = false;

		async function fetchApps() {
			const appsData = await getApps();
			if (cancelled) {
				return;
			}
			setApps(Array.isArray(appsData) ? appsData : []);
			setIsLoading(false);
		}

		fetchApps();

		return () => {
			cancelled = true;
		};
	}, []);

	const hasPaymentApps = useMemo(
		() => apps.some((app) => isPaymentApp(app)),
		[apps]
	);

	const visibleFilterTabs = useMemo(
		() =>
			hasPaymentApps
				? FILTER_TABS
				: FILTER_TABS.filter((tab) => tab.id !== "payments"),
		[hasPaymentApps]
	);

	useEffect(() => {
		if (!parent) {
			return;
		}

		const mapped = mapRouteParentToFilter(parent);

		if (mapped === "payments" && !hasPaymentApps) {
			setActiveFilter("all");
			return;
		}

		if (visibleFilterTabs.some((tab) => tab.id === mapped)) {
			setActiveFilter(mapped);
		}
	}, [parent, visibleFilterTabs, hasPaymentApps]);

	useEffect(() => {
		if (activeFilter === "payments" && !hasPaymentApps) {
			setActiveFilter("all");
		}
	}, [activeFilter, hasPaymentApps]);

	const filteredApps = useMemo(() => {
		let list = apps;

		if (activeFilter !== "all") {
			list = list.filter((app) => {
				const normalized = normalizeParent(app.parent_menu);

				if (activeFilter === "integrations") {
					return (
						normalized === "integrations" ||
						normalized === "integration" ||
						app.id === "acf-integration"
					);
				}

				if (activeFilter === "payments") {
					return isPaymentApp(app);
				}

				if (app.id === "acf-integration" || isPaymentApp(app)) {
					return false;
				}

				return normalized === activeFilter;
			});
		}

		const query = searchQuery.trim().toLowerCase();
		if (!query) {
			return list;
		}

		return list.filter((app) => {
			const title = String(app.title || "").toLowerCase();
			const description = String(app.description || "").toLowerCase();
			return title.includes(query) || description.includes(query);
		});
	}, [apps, activeFilter, searchQuery]);

	const clearGridTimers = useCallback(() => {
		gridTimers.current.forEach((id) => clearTimeout(id));
		gridTimers.current = [];
	}, []);

	const scheduleGridTimer = useCallback((fn, delay) => {
		const id = setTimeout(fn, delay);
		gridTimers.current.push(id);
		return id;
	}, []);

	useEffect(() => {
		if (isLoading) {
			return;
		}

		if (skipGridAnimation.current) {
			skipGridAnimation.current = false;
			setDisplayApps(filteredApps);
			return;
		}

		clearGridTimers();
		setGridTransition("is-exiting");

		scheduleGridTimer(() => {
			setDisplayApps(filteredApps);
			setGridTransition("is-entering");
			scheduleGridTimer(() => setGridTransition(""), GRID_ENTER_MS);
		}, GRID_EXIT_MS);

		return clearGridTimers;
	}, [filteredApps, isLoading, clearGridTimers, scheduleGridTimer]);

	const handleAcfNotice = useCallback(() => {
		setShowAcfNotice(false);
		setTimeout(() => {
			setShowAcfNotice(true);
			window.scrollTo({ top: 0, behavior: "smooth" });
		}, 10);
		setTimeout(() => setShowAcfNotice(false), ACF_NOTICE_MS);
	}, []);

	const handleSearchChange = useCallback((event) => {
		setSearchQuery(event.target.value);
	}, []);

	const gridClassName = gridTransition
		? `cf7apps-apps-container ${gridTransition}`
		: "cf7apps-apps-container";

	const emptyClassName = gridTransition
		? `cf7apps-dashboard-empty ${gridTransition}`
		: "cf7apps-dashboard-empty";

	return (
		<div className="cf7apps-dashboard">
			<div className="cf7apps-dashboard__panel">
				<div className="cf7apps-dashboard__hero">
					<h2 className="cf7apps-dashboard__hero-title">
						{__(
							"Unleash the full potential of Contact Form 7!",
							"cf7apps"
						)}
					</h2>
					<p className="cf7apps-dashboard__hero-subtitle">
						{__(
							"Simplify, customize, and enhance your form building experience.",
							"cf7apps"
						)}
					</p>
				</div>

				{showAcfNotice && (
					<div className="cf7apps-dashboard-notice cf7apps-acf-install-notice">
						<CF7AppsNotice
							type="warning"
							text={getAcfInstallNoticeHtml()}
						/>
					</div>
				)}

				<div className="cf7apps-dashboard__toolbar">
					<label className="cf7apps-dashboard__search">
						<span className="screen-reader-text">
							{__("Search apps", "cf7apps")}
						</span>
						{assetsBase && (
							<img
								className="cf7apps-dashboard__search-icon"
								src={toolbarIcon("search.svg")}
								alt=""
								width={13}
								height={13}
								decoding="async"
							/>
						)}
						<input
							type="text"
							className="cf7apps-dashboard__search-input"
							placeholder={__("Search", "cf7apps")}
							value={searchQuery}
							onChange={handleSearchChange}
							autoComplete="off"
							spellCheck={false}
						/>
					</label>
					<nav
						className="cf7apps-dashboard__filters"
						role="tablist"
						aria-label={__("Filter apps", "cf7apps")}
					>
						{visibleFilterTabs.map((tab) => {
							const isActive = activeFilter === tab.id;

							return (
								<button
									key={tab.id}
									type="button"
									role="tab"
									aria-selected={isActive}
									className={
										isActive
											? "cf7apps-dashboard__filter is-active"
											: "cf7apps-dashboard__filter"
									}
									onClick={() => setActiveFilter(tab.id)}
								>
									{tab.icon && (
										<img
											className="cf7apps-dashboard__filter-icon"
											src={toolbarIcon(tab.icon)}
											alt=""
											width={12}
											height={12}
										/>
									)}
									<span className="cf7apps-dashboard__filter-label">
										{tab.label}
									</span>
								</button>
							);
						})}
					</nav>
				</div>

				<div className={gridClassName}>
					{isLoading ? (
						CARD_SKELETON
					) : displayApps.length > 0 ? (
						displayApps.map((app) => (
							<CF7AppsApp
								key={app.id}
								settings={app}
								onShowAcfNotice={
									app.id === "acf-integration"
										? handleAcfNotice
										: undefined
								}
							/>
						))
					) : (
						<p className={emptyClassName}>
							{__("No apps match your search or filter.", "cf7apps")}
						</p>
					)}
				</div>
			</div>
		</div>
	);
};

export default Apps;
