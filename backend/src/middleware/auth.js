import { verifyAccessToken } from "../utils/tokens.js";

function extractBearerToken(req) {
  const header = req.headers.authorization || "";
  const [scheme, token] = header.split(" ");
  return scheme === "Bearer" ? token : null;
}

export function requireCitizenAuth(req, res, next) {
  const token = extractBearerToken(req);
  if (!token) return res.status(401).json({ error: { code: "UNAUTHENTICATED", message: "Missing bearer token" } });

  try {
    const payload = verifyAccessToken(token);
    if (payload.role !== "citizen") throw new Error("Wrong token role");
    req.citizenId = payload.sub;
    next();
  } catch {
    res.status(401).json({ error: { code: "INVALID_TOKEN", message: "Invalid or expired access token" } });
  }
}

export function requireStaffAuth(allowedRoles = null) {
  return (req, res, next) => {
    const token = extractBearerToken(req);
    if (!token) return res.status(401).json({ error: { code: "UNAUTHENTICATED", message: "Missing bearer token" } });

    try {
      const payload = verifyAccessToken(token);
      if (payload.role !== "staff") throw new Error("Wrong token role");
      if (allowedRoles && !allowedRoles.includes(payload.staffRole)) {
        return res.status(403).json({ error: { code: "FORBIDDEN", message: "Role not permitted for this action" } });
      }
      req.staffId = payload.sub;
      req.staffRole = payload.staffRole;
      next();
    } catch {
      res.status(401).json({ error: { code: "INVALID_TOKEN", message: "Invalid or expired access token" } });
    }
  };
}
