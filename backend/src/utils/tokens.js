import jwt from "jsonwebtoken";

const ACCESS_SECRET = process.env.JWT_ACCESS_SECRET || "dev-access-secret";
const REFRESH_SECRET = process.env.JWT_REFRESH_SECRET || "dev-refresh-secret";
const ACCESS_TTL = Number(process.env.ACCESS_TOKEN_TTL_SECONDS || 3600);
const REFRESH_TTL = Number(process.env.REFRESH_TOKEN_TTL_SECONDS || 2592000);

export function issueCitizenTokens(citizenId) {
  const accessToken = jwt.sign({ sub: citizenId, role: "citizen" }, ACCESS_SECRET, { expiresIn: ACCESS_TTL });
  const refreshToken = jwt.sign({ sub: citizenId, role: "citizen", type: "refresh" }, REFRESH_SECRET, { expiresIn: REFRESH_TTL });
  return { accessToken, refreshToken, expiresIn: ACCESS_TTL };
}

export function issueStaffTokens(staffUser) {
  const accessToken = jwt.sign(
    { sub: staffUser.id, role: "staff", staffRole: staffUser.role },
    ACCESS_SECRET,
    { expiresIn: ACCESS_TTL }
  );
  return { accessToken, expiresIn: ACCESS_TTL };
}

export function verifyAccessToken(token) {
  return jwt.verify(token, ACCESS_SECRET);
}

export function verifyRefreshToken(token) {
  const payload = jwt.verify(token, REFRESH_SECRET);
  if (payload.type !== "refresh") throw new Error("Not a refresh token");
  return payload;
}
